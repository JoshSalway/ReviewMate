<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessIntegration;
use Illuminate\Support\Facades\Http;

class XeroService
{
    const AUTH_URL = 'https://login.xero.com/identity/connect/authorize';

    const TOKEN_URL = 'https://identity.xero.com/connect/token';

    const API_BASE = 'https://api.xero.com/api.xro/2.0';

    public function __construct(protected Business $business) {}

    protected function integration(): ?BusinessIntegration
    {
        return $this->business->integration('xero');
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.xero.client_id'),
            'redirect_uri' => route('integrations.xero.callback'),
            'scope' => 'openid profile email accounting.contacts.read accounting.transactions.read offline_access',
            'state' => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::withBasicAuth(
            config('services.xero.client_id'),
            config('services.xero.client_secret'),
        )->asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('integrations.xero.callback'),
        ]);

        return $response->json();
    }

    public function refreshToken(): void
    {
        $integration = $this->integration();

        $response = Http::withBasicAuth(
            config('services.xero.client_id'),
            config('services.xero.client_secret'),
        )->asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $integration?->refresh_token,
        ]);

        $data = $response->json();

        $integration?->update([
            'access_token'     => $data['access_token'],
            'refresh_token'    => $data['refresh_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 1800),
        ]);
    }

    public function getTenants(): array
    {
        $response = Http::withToken($this->integration()?->access_token)
            ->get('https://api.xero.com/connections');

        return $response->json() ?? [];
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $integration = $this->integration();

        if ($integration?->token_expires_at?->isPast()) {
            $this->refreshToken();
            $this->business->load('integrations');
        }

        $response = Http::withToken($this->integration()?->access_token)
            ->withHeaders(['Xero-tenant-id' => $this->integration()?->getMeta('tenant_id')])
            ->$method(self::API_BASE.$path, $data);

        return $response->json() ?? [];
    }

    public function getInvoice(string $invoiceId): ?array
    {
        $data = $this->request('get', "/Invoices/{$invoiceId}");

        return $data['Invoices'][0] ?? null;
    }

    public function getContact(string $contactId): ?array
    {
        $data = $this->request('get', "/Contacts/{$contactId}");

        return $data['Contacts'][0] ?? null;
    }

    public function isConnected(): bool
    {
        return $this->integration()?->isConnected() ?? false;
    }
}
