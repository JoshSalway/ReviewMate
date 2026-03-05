<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessIntegration;
use Illuminate\Support\Facades\Http;

class ServiceM8Service
{
    const API_BASE = 'https://api.servicem8.com/api_1.0';
    const AUTH_URL = 'https://go.servicem8.com/oauth/authorize';
    const TOKEN_URL = 'https://go.servicem8.com/oauth/access_token';

    public function __construct(protected Business $business) {}

    protected function integration(): ?BusinessIntegration
    {
        return $this->business->integration('servicem8');
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => config('services.servicem8.client_id'),
            'redirect_uri'  => route('integrations.servicem8.callback'),
            'scope'         => 'read_jobs read_clients manage_jobs',
            'state'         => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.servicem8.client_id'),
            'client_secret' => config('services.servicem8.client_secret'),
            'redirect_uri'  => route('integrations.servicem8.callback'),
            'code'          => $code,
        ]);

        return $response->json() ?? [];
    }

    public function refreshToken(): void
    {
        $integration = $this->integration();

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'client_id'     => config('services.servicem8.client_id'),
            'client_secret' => config('services.servicem8.client_secret'),
            'refresh_token' => $integration?->refresh_token,
        ]);

        $data = $response->json() ?? [];

        $integration?->update([
            'access_token'     => $data['access_token'],
            'refresh_token'    => $data['refresh_token'] ?? $integration->refresh_token,
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
            ->$method(self::API_BASE . $path, $data);

        return $response->json() ?? [];
    }

    public function getJob(string $jobUuid): array
    {
        return $this->request('get', "/job/{$jobUuid}.json");
    }

    public function getJobContact(string $jobUuid): ?array
    {
        $contacts = $this->request('get', "/jobcontact.json?\$filter=job_uuid eq '{$jobUuid}'");

        return is_array($contacts) ? ($contacts[0] ?? null) : null;
    }

    public function getContact(string $contactUuid): array
    {
        return $this->request('get', "/clientcontact/{$contactUuid}.json");
    }

    public function isConnected(): bool
    {
        return $this->integration()?->isConnected() ?? false;
    }
}
