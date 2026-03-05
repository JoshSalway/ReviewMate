<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessJobberJobCompletion;
use App\Models\Business;
use App\Services\JobberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class JobberController extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session(['jobber_oauth_state' => $state]);

        $service = new JobberService(Auth::user()->currentBusiness());

        return redirect($service->getAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('jobber_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->currentBusiness();
        $service  = new JobberService($business);
        $tokens   = $service->exchangeCodeForToken($request->code);
        $service->storeTokens($tokens);

        return redirect()->route('settings.integrations')
            ->with('success', 'Jobber connected successfully!');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        Auth::user()->currentBusiness()->integrations()->where('provider', 'jobber')->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'Jobber disconnected.');
    }

    public function toggleAutoSend(Request $request): RedirectResponse
    {
        $integration = Auth::user()->currentBusiness()->integration('jobber');

        $integration?->update([
            'auto_send_reviews' => ! $integration->auto_send_reviews,
        ]);

        return back()->with('success', 'Auto-send setting updated.');
    }

    /**
     * Jobber webhook — fires when a job is updated.
     * We dispatch a job to check if it's completed and send a review request.
     */
    public function webhook(Request $request, Business $business): JsonResponse
    {
        $payload = $request->all();

        // Jobber wraps the event in data.webHookEvent
        $event  = $payload['data']['webHookEvent'] ?? $payload;
        $topic  = $event['topic'] ?? '';
        $jobId  = $event['data']['jobId'] ?? null;

        if ($topic !== 'JOB_UPDATED' || ! $jobId) {
            return response()->json(['status' => 'ignored']);
        }

        $integration = $business->integration('jobber');

        if ($integration?->auto_send_reviews) {
            ProcessJobberJobCompletion::dispatch($business, $jobId);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
