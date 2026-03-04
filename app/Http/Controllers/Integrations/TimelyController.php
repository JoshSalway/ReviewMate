<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTimelyAppointmentCompleted;
use App\Models\Business;
use App\Services\TimelyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TimelyController extends Controller
{
    /**
     * Step 1: Redirect the authenticated user to Timely OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session(['timely_oauth_state' => $state]);

        $service = new TimelyService(Auth::user()->currentBusiness());

        return redirect($service->getAuthorizationUrl($state));
    }

    /**
     * Step 2: Handle the OAuth callback and store tokens.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('timely_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->currentBusiness();
        $service  = new TimelyService($business);
        $tokens   = $service->exchangeCodeForToken($request->code);

        $business->update([
            'timely_access_token'     => $tokens['access_token'] ?? null,
            'timely_refresh_token'    => $tokens['refresh_token'] ?? null,
            'timely_token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            'timely_account_id'       => $tokens['account_id'] ?? null,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Timely connected successfully!');
    }

    /**
     * Step 3: Disconnect (clear tokens).
     */
    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->update([
            'timely_access_token'     => null,
            'timely_refresh_token'    => null,
            'timely_token_expires_at' => null,
            'timely_account_id'       => null,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Timely disconnected.');
    }

    /**
     * Toggle auto-send setting.
     */
    public function toggleAutoSend(): RedirectResponse
    {
        $business = Auth::user()->currentBusiness();
        $business->update(['timely_auto_send_reviews' => ! $business->timely_auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }

    /**
     * Webhook endpoint — Timely calls this when an appointment status changes.
     * Each business has a unique URL identified by their UUID.
     */
    public function webhook(Request $request, Business $business): JsonResponse
    {
        $event = $request->input('event');

        // Only process appointment completion events
        if ($event !== 'appointment.completed' && $event !== 'appointment.updated') {
            return response()->json(['status' => 'ignored']);
        }

        $data   = $request->input('data', []);
        $status = $data['status'] ?? null;

        // For appointment.updated, only proceed if status is 'completed'
        if ($event === 'appointment.updated' && $status !== 'completed') {
            return response()->json(['status' => 'ignored']);
        }

        if ($business->timely_auto_send_reviews) {
            ProcessTimelyAppointmentCompleted::dispatch($business, $data);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
