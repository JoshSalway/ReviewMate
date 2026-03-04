<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessXeroInvoicePaid;
use App\Models\Business;
use App\Services\XeroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class XeroController extends Controller
{
    public function connect(Request $request)
    {
        $business = Auth::user()->business;
        $state = Str::random(40);
        session(['xero_oauth_state' => $state]);

        $service = new XeroService($business);

        return redirect($service->getAuthorizationUrl($state));
    }

    public function callback(Request $request)
    {
        if ($request->state !== session('xero_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->business;
        $service = new XeroService($business);
        $tokens = $service->exchangeCodeForToken($request->code);

        $business->update([
            'xero_access_token' => $tokens['access_token'],
            'xero_refresh_token' => $tokens['refresh_token'],
            'xero_token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 1800),
        ]);

        // Fetch tenant ID (Xero organisation) — take first
        $tempService = new XeroService($business->fresh());
        $tenants = $tempService->getTenants();
        $tenantId = $tenants[0]['tenantId'] ?? null;

        $business->update(['xero_tenant_id' => $tenantId]);

        return redirect()->route('settings.integrations')->with('success', 'Xero connected successfully!');
    }

    public function disconnect()
    {
        Auth::user()->business->update([
            'xero_access_token' => null,
            'xero_refresh_token' => null,
            'xero_token_expires_at' => null,
            'xero_tenant_id' => null,
        ]);

        return redirect()->route('settings.integrations')->with('success', 'Xero disconnected.');
    }

    public function webhook(Request $request, Business $business)
    {
        // Xero webhooks use HMAC-SHA256 signature verification
        $payload = $request->getContent();
        $signature = $request->header('x-xero-signature');
        $expected = base64_encode(hash_hmac('sha256', $payload, config('services.xero.webhook_key'), true));

        if (! hash_equals($expected, $signature ?? '')) {
            return response()->json(['status' => 'invalid signature'], 401);
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            if (($event['eventType'] ?? '') === 'UPDATE' && ($event['resourceUrl'] ?? '') !== '') {
                // Extract invoice ID from resource URL
                preg_match('/Invoices\/([^\/\?]+)/', $event['resourceUrl'], $matches);
                $invoiceId = $matches[1] ?? null;

                if ($invoiceId && $business->xero_auto_send_reviews) {
                    ProcessXeroInvoicePaid::dispatch($business, $invoiceId);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function toggleAutoSend()
    {
        $business = Auth::user()->business;
        $business->update(['xero_auto_send_reviews' => ! $business->xero_auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }
}
