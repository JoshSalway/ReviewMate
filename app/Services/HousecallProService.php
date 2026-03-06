<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessIntegration;
use Illuminate\Support\Facades\Http;

class HousecallProService
{
    const AUTH_URL = 'https://pro.housecallpro.com/oauth/authorize';

    const TOKEN_URL = 'https://pro.housecallpro.com/oauth/token';

    const API_BASE = 'https://api.housecallpro.com';

    public function __construct(protected Business $business) {}

    protected function integration(): ?BusinessIntegration
    {
        return $this->business->integration('housecallpro');
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.housecallpro.client_id'),
            'redirect_uri' => route('integrations.housecallpro.callback'),
            'state' => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.housecallpro.client_id'),
            'client_secret' => config('services.housecallpro.client_secret'),
            'redirect_uri' => route('integrations.housecallpro.callback'),
            'code' => $code,
        ]);

        return $response->json() ?? [];
    }

    public function storeTokens(array $tokens): BusinessIntegration
    {
        return BusinessIntegration::updateOrCreate(
            ['business_id' => $this->business->id, 'provider' => 'housecallpro'],
            [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            ]
        );
    }

    public function refreshToken(): void
    {
        $integration = $this->integration();

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.housecallpro.client_id'),
            'client_secret' => config('services.housecallpro.client_secret'),
            'refresh_token' => $integration->refresh_token,
        ]);

        $data = $response->json() ?? [];

        $integration->update([
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
            $integration = $this->integration();
        }

        $response = Http::withToken($integration->access_token)
            ->$method(self::API_BASE.$path, $data);

        return $response->json() ?? [];
    }

    public function getJob(string $jobId): array
    {
        return $this->request('get', "/jobs/{$jobId}");
    }

    public function isConnected(): bool
    {
        return $this->integration()?->isConnected() ?? false;
    }
}
