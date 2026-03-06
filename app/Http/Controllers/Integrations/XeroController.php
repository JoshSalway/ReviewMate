<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessXeroInvoicePaid;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Services\XeroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class XeroController extends Controller
{
    public function connect(Request $request)
    {
        $state = Str::random(40);
        session(['xero_oauth_state' => $state]);

        return redirect((new XeroService(Auth::user()->currentBusiness()))->getAuthorizationUrl($state));
    }

    public function callback(Request $request)
    {
        if ($request->state !== session('xero_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $business = Auth::user()->currentBusiness();
        $service = new XeroService($business);
        $tokens = $service->exchangeCodeForToken($request->code);

        BusinessIntegration::updateOrCreate(
            ['business_id' => $business->id, 'provider' => 'xero'],
            [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 1800),
            ]
        );

        // Reload so XeroService can use the new token, then fetch tenant ID
        $business->load('integrations');
        $tenants = (new XeroService($business))->getTenants();
        $tenantId = $tenants[0]['tenantId'] ?? null;

        $business->integration('xero')?->mergeMeta(['tenant_id' => $tenantId]);

        return redirect()->route('settings.integrations')->with('success', 'Xero connected successfully!');
    }

    public function disconnect()
    {
        Auth::user()->currentBusiness()->integrations()->where('provider', 'xero')->delete();

        return redirect()->route('settings.integrations')->with('success', 'Xero disconnected.');
    }

    public function webhook(Request $request, Business $business)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-xero-signature');
        $expected = base64_encode(hash_hmac('sha256', $payload, config('services.xero.webhook_key'), true));

        if (! hash_equals($expected, $signature ?? '')) {
            return response()->json(['status' => 'invalid signature'], 401);
        }

        foreach ($request->input('events', []) as $event) {
            if (($event['eventType'] ?? '') === 'UPDATE' && ($event['resourceUrl'] ?? '') !== '') {
                preg_match('/Invoices\/([^\/\?]+)/', $event['resourceUrl'], $matches);
                $invoiceId = $matches[1] ?? null;
                $integration = $business->integration('xero');

                if ($invoiceId && $integration?->auto_send_reviews) {
                    ProcessXeroInvoicePaid::dispatch($business, $invoiceId);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function toggleAutoSend()
    {
        $integration = Auth::user()->currentBusiness()->integration('xero');
        $integration?->update(['auto_send_reviews' => ! $integration->auto_send_reviews]);

        return back()->with('success', 'Auto-send setting updated.');
    }
}
