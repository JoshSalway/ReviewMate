<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSimproJobComplete;
use App\Models\Business;
use App\Services\SimproService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SimproController extends Controller
{
    /**
     * Step 1: Validate the company URL and redirect to Simpro OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        $request->validate([
            'company_url' => 'required|string|regex:/^[a-zA-Z0-9\-]+\.simprocloud\.com$/',
        ]);

        $companyUrl = $request->company_url;
        $business   = Auth::user()->currentBusiness();
        $state      = Str::random(40);

        session([
            'simpro_oauth_state'       => $state,
            'simpro_oauth_company_url' => $companyUrl,
        ]);

        $service = new SimproService($business);

        return redirect($service->getAuthorizationUrl($state, $companyUrl));
    }

    /**
     * Step 2: Handle the OAuth callback and store tokens.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('simpro_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $companyUrl = session('simpro_oauth_company_url');

        if (! $companyUrl) {
            abort(403, 'Missing company URL in session');
        }

        $business = Auth::user()->currentBusiness();
        $service  = new SimproService($business);
        $tokens   = $service->exchangeCodeForToken($request->code, $companyUrl);

        $business->update([
            'simpro_access_token'     => $tokens['access_token'] ?? null,
            'simpro_refresh_token'    => $tokens['refresh_token'] ?? null,
            'simpro_token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            'simpro_company_url'      => $companyUrl,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Simpro connected successfully!');
    }

    /**
     * Step 3: Disconnect (clear tokens).
     */
    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->update([
            'simpro_access_token'     => null,
            'simpro_refresh_token'    => null,
            'simpro_token_expires_at' => null,
            'simpro_company_url'      => null,
        ]);

        return redirect()->route('settings.integrations')
            ->with('success', 'Simpro disconnected.');
    }

    /**
     * Toggle auto-send setting.
     */
    public function toggleAutoSend(): RedirectResponse
    {
        $business = Auth::user()->currentBusiness();

        $business->update([
            'simpro_auto_send_reviews' => ! $business->simpro_auto_send_reviews,
        ]);

        return back()->with('success', 'Auto-send setting updated.');
    }

    /**
     * Webhook endpoint — Simpro calls this when a job status changes.
     * Each business has a unique URL identified by their UUID.
     */
    public function webhook(Request $request, Business $business): JsonResponse
    {
        $payload = $request->all();
        $event   = $payload['event'] ?? '';

        if ($event !== 'job.status.changed') {
            return response()->json(['status' => 'ignored']);
        }

        $data   = $payload['data'] ?? [];
        $status = $data['status'] ?? '';
        $jobId  = $data['jobId'] ?? null;

        if ($status !== 'Complete') {
            return response()->json(['status' => 'ignored']);
        }

        if (! $jobId) {
            return response()->json(['status' => 'no job id'], 400);
        }

        if ($business->simpro_auto_send_reviews) {
            ProcessSimproJobComplete::dispatch($business, (int) $jobId);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
