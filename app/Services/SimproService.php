<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessIntegration;
use Illuminate\Support\Facades\Http;

class SimproService
{
    const AUTH_PATH = '/oauth2/authorize';

    const TOKEN_PATH = '/oauth2/token';

    public function __construct(protected Business $business) {}

    protected function integration(): ?BusinessIntegration
    {
        return $this->business->integration('simpro');
    }

    protected function baseUrl(): string
    {
        return 'https://'.$this->integration()?->getMeta('company_url');
    }

    public function getAuthorizationUrl(string $state, string $companyUrl): string
    {
        return 'https://'.$companyUrl.self::AUTH_PATH.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.simpro.client_id'),
            'redirect_uri' => route('integrations.simpro.callback'),
            'scope' => 'read',
            'state' => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code, string $companyUrl): array
    {
        $response = Http::asForm()->post('https://'.$companyUrl.self::TOKEN_PATH, [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.simpro.client_id'),
            'client_secret' => config('services.simpro.client_secret'),
            'redirect_uri' => route('integrations.simpro.callback'),
            'code' => $code,
        ]);

        return $response->json() ?? [];
    }

    public function refreshToken(): void
    {
        $integration = $this->integration();

        $response = Http::asForm()->post($this->baseUrl().self::TOKEN_PATH, [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.simpro.client_id'),
            'client_secret' => config('services.simpro.client_secret'),
            'refresh_token' => $integration?->refresh_token,
        ]);

        $data = $response->json() ?? [];

        $integration?->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $integration->refresh_token,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $integration = $this->integration();

        if ($integration?->token_expires_at?->isPast()) {
            $this->refreshToken();
            $this->business->load('integrations');
        }

        $response = Http::withToken($this->integration()?->access_token)
            ->$method($this->baseUrl().'/api/v1.0'.$path, $data);

        return $response->json() ?? [];
    }

    public function getJob(int $jobId): ?array
    {
        $data = $this->request('get', "/jobs/{$jobId}");

        return $data ?: null;
    }

    public function getCustomer(int $customerId): ?array
    {
        $data = $this->request('get', "/customers/{$customerId}");

        return $data ?: null;
    }

    public function isConnected(): bool
    {
        return $this->integration()?->isConnected() ?? false;
    }
}
