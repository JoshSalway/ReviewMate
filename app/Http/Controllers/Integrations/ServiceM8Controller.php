<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessServiceM8JobCompletion;
use App\Models\Business;
use App\Services\ServiceM8Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ServiceM8Controller extends Controller
{
    /**
     * Step 1: Redirect the authenticated user to ServiceM8 OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        $business = Auth::user()->currentBusiness();
        $state    = Str::random(40);

        session(['servicem8_oauth_state' => $state]);

        $service = new ServiceM8Service($business);

        return redirect($service->getAuthorizationUrl($state));
    }

    /**
     * Step 2: Handle the OAuth callback and store tokens.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('servicem8_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->currentBusiness();
        $service  = new ServiceM8Service($business);
        $tokens   = $service->exchangeCodeForToken($request->code);

        $business->update([
            'servicem8_access_token'     => $tokens['access_token'] ?? null,
            'servicem8_refresh_token'    => $tokens['refresh_token'] ?? null,
            'servicem8_token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'ServiceM8 connected successfully!');
    }

    /**
     * Step 3: Disconnect (clear tokens).
     */
    public function disconnect(Request $request): RedirectResponse
    {
        Auth::user()->currentBusiness()->update([
            'servicem8_access_token'     => null,
            'servicem8_refresh_token'    => null,
            'servicem8_token_expires_at' => null,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'ServiceM8 disconnected.');
    }

    /**
     * Toggle auto-send setting.
     */
    public function toggleAutoSend(Request $request): RedirectResponse
    {
        $business = Auth::user()->currentBusiness();

        $business->update([
            'servicem8_auto_send_reviews' => ! $business->servicem8_auto_send_reviews,
        ]);

        return back()->with('success', 'Auto-send setting updated.');
    }

    /**
     * Webhook endpoint — ServiceM8 calls this when a job status changes.
     * Each business has a unique URL identified by their UUID.
     */
    public function webhook(Request $request, Business $business): JsonResponse
    {
        $payload    = $request->all();
        $entryPoint = $payload['entry_point'] ?? '';

        if ($entryPoint !== 'JobCompletion') {
            return response()->json(['status' => 'ignored']);
        }

        $jobUuid = $payload['object_uuid'] ?? null;

        if (! $jobUuid) {
            return response()->json(['status' => 'no job uuid'], 400);
        }

        if ($business->servicem8_auto_send_reviews) {
            ProcessServiceM8JobCompletion::dispatch($business, $jobUuid);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
