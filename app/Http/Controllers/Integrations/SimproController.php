<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSimproJobComplete;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Services\SimproService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SimproController extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $request->validate([
            'company_url' => 'required|string|regex:/^[a-zA-Z0-9\-]+\.simprocloud\.com$/',
        ]);

        $companyUrl = $request->company_url;
        $state = Str::random(40);

        session([
            'simpro_oauth_state' => $state,
            'simpro_oauth_company_url' => $companyUrl,
        ]);

        return redirect(
            (new SimproService(Auth::user()->currentBusiness()))->getAuthorizationUrl($state, $companyUrl)
        );
    }

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
        $tokens = (new SimproService($business))->exchangeCodeForToken($request->code, $companyUrl);

        BusinessIntegration::updateOrCreate(
            ['business_id' => $business->id, 'provider' => 'simpro'],
            [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                'meta' => ['company_url' => $companyUrl],
            ]
        );

        return redirect()->route('settings.integrations')
            ->with('success', 'Simpro connected successfully!');
    }

    public function disconnect(): RedirectResponse
    {
        Auth::user()->currentBusiness()->integrations()->where('provider', 'simpro')->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'Simpro disconnected.');
    }

    public function toggleAutoSend(): RedirectResponse
    {
        $integration = Auth::user()->currentBusiness()->integration('simpro');
        $integration?->update(['auto_send_reviews' => ! $integration->auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }

    public function webhook(Request $request, Business $business): JsonResponse
    {
        // Verify Simpro webhook signature (HMAC-SHA256 over the raw body)
        $secret = config('services.simpro.webhook_secret');
        if ($secret) {
            $signature = $request->header('x-simpro-signature');
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, $signature ?? '')) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();
        $event = $payload['event'] ?? '';

        if ($event !== 'job.status.changed') {
            return response()->json(['status' => 'ignored']);
        }

        $data = $payload['data'] ?? [];
        $status = $data['status'] ?? '';
        $jobId = $data['jobId'] ?? null;

        if ($status !== 'Complete') {
            return response()->json(['status' => 'ignored']);
        }

        if (! $jobId) {
            return response()->json(['status' => 'no job id'], 400);
        }

        $integration = $business->integration('simpro');

        if ($integration?->auto_send_reviews) {
            ProcessSimproJobComplete::dispatch($business, (int) $jobId);

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'auto_send_disabled']);
    }
}
