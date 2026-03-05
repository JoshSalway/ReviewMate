<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessServiceM8JobCompletion;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Services\ServiceM8Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ServiceM8Controller extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $business = Auth::user()->currentBusiness();
        $state    = Str::random(40);

        session(['servicem8_oauth_state' => $state]);

        return redirect((new ServiceM8Service($business))->getAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('servicem8_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->currentBusiness();
        $tokens   = (new ServiceM8Service($business))->exchangeCodeForToken($request->code);

        BusinessIntegration::updateOrCreate(
            ['business_id' => $business->id, 'provider' => 'servicem8'],
            [
                'access_token'     => $tokens['access_token'] ?? null,
                'refresh_token'    => $tokens['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            ]
        );

        return redirect()->route('settings.integrations')
            ->with('success', 'ServiceM8 connected successfully!');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        Auth::user()->currentBusiness()->integrations()->where('provider', 'servicem8')->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'ServiceM8 disconnected.');
    }

    public function toggleAutoSend(Request $request): RedirectResponse
    {
        $integration = Auth::user()->currentBusiness()->integration('servicem8');
        $integration?->update(['auto_send_reviews' => ! $integration->auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }

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

        $integration = $business->integration('servicem8');

        if ($integration?->auto_send_reviews) {
            ProcessServiceM8JobCompletion::dispatch($business, $jobUuid);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
