<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessHousecallProJobCompletion;
use App\Models\Business;
use App\Services\HousecallProService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HousecallProController extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session(['housecallpro_oauth_state' => $state]);

        $service = new HousecallProService(Auth::user()->currentBusiness());

        return redirect($service->getAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('housecallpro_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->currentBusiness();
        $service  = new HousecallProService($business);
        $tokens   = $service->exchangeCodeForToken($request->code);
        $service->storeTokens($tokens);

        return redirect()->route('settings.integrations')
            ->with('success', 'Housecall Pro connected successfully!');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        Auth::user()->currentBusiness()->housecallProIntegration?->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'Housecall Pro disconnected.');
    }

    public function toggleAutoSend(Request $request): RedirectResponse
    {
        $integration = Auth::user()->currentBusiness()->housecallProIntegration;

        $integration?->update([
            'auto_send_reviews' => ! $integration->auto_send_reviews,
        ]);

        return back()->with('success', 'Auto-send setting updated.');
    }

    /**
     * Housecall Pro webhook — fires when a job is completed.
     * Payload includes customer data so no extra API call needed.
     */
    public function webhook(Request $request, Business $business): JsonResponse
    {
        $payload   = $request->all();
        $eventType = $payload['event_action'] ?? $payload['event'] ?? '';

        if ($eventType !== 'job.completed') {
            return response()->json(['status' => 'ignored']);
        }

        $jobData = $payload['event_resource'] ?? $payload['data'] ?? [];

        if (empty($jobData)) {
            return response()->json(['status' => 'no job data'], 400);
        }

        $integration = $business->housecallProIntegration;

        if ($integration?->auto_send_reviews) {
            ProcessHousecallProJobCompletion::dispatch($business, $jobData);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
