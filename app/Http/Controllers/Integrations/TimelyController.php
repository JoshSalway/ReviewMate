<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTimelyAppointmentCompleted;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Services\TimelyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TimelyController extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session(['timely_oauth_state' => $state]);

        return redirect((new TimelyService(Auth::user()->currentBusiness()))->getAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('timely_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->currentBusiness();
        $tokens = (new TimelyService($business))->exchangeCodeForToken($request->code);

        BusinessIntegration::updateOrCreate(
            ['business_id' => $business->id, 'provider' => 'timely'],
            [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                'meta' => ['account_id' => $tokens['account_id'] ?? null],
            ]
        );

        return redirect()->route('settings.integrations')
            ->with('success', 'Timely connected successfully!');
    }

    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->integrations()->where('provider', 'timely')->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'Timely disconnected.');
    }

    public function toggleAutoSend(): RedirectResponse
    {
        $integration = Auth::user()->currentBusiness()->integration('timely');
        $integration?->update(['auto_send_reviews' => ! $integration->auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }

    public function webhook(Request $request, Business $business): JsonResponse
    {
        // Verify Timely webhook signature (HMAC-SHA256 over the raw body)
        $secret = config('services.timely.webhook_secret');
        if ($secret) {
            $signature = $request->header('x-timely-signature');
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, $signature ?? '')) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $event = $request->input('event');

        if ($event !== 'appointment.completed' && $event !== 'appointment.updated') {
            return response()->json(['status' => 'ignored']);
        }

        $data = $request->input('data', []);
        $status = $data['status'] ?? null;

        if ($event === 'appointment.updated' && $status !== 'completed') {
            return response()->json(['status' => 'ignored']);
        }

        $integration = $business->integration('timely');

        if ($integration?->auto_send_reviews) {
            ProcessTimelyAppointmentCompleted::dispatch($business, $data);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
