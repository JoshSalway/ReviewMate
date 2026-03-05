<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessIntegration;
use Illuminate\Support\Facades\Http;

class JobberService
{
    const AUTH_URL  = 'https://api.getjobber.com/api/oauth/authorize';
    const TOKEN_URL = 'https://api.getjobber.com/api/oauth/token';
    const GQL_URL   = 'https://api.getjobber.com/api/graphql';

    public function __construct(protected Business $business) {}

    protected function integration(): ?BusinessIntegration
    {
        return $this->business->integration('jobber');
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => config('services.jobber.client_id'),
            'redirect_uri'  => route('integrations.jobber.callback'),
            'state'         => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.jobber.client_id'),
            'client_secret' => config('services.jobber.client_secret'),
            'redirect_uri'  => route('integrations.jobber.callback'),
            'code'          => $code,
        ]);

        return $response->json() ?? [];
    }

    public function storeTokens(array $tokens): BusinessIntegration
    {
        return BusinessIntegration::updateOrCreate(
            ['business_id' => $this->business->id, 'provider' => 'jobber'],
            [
                'access_token'     => $tokens['access_token'] ?? null,
                'refresh_token'    => $tokens['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            ]
        );
    }

    public function refreshToken(): void
    {
        $integration = $this->integration();

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'client_id'     => config('services.jobber.client_id'),
            'client_secret' => config('services.jobber.client_secret'),
            'refresh_token' => $integration->refresh_token,
        ]);

        $data = $response->json() ?? [];

        $integration->update([
            'access_token'     => $data['access_token'],
            'refresh_token'    => $data['refresh_token'] ?? $integration->refresh_token,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);
    }

    protected function query(string $query, array $variables = []): array
    {
        $integration = $this->integration();

        if ($integration?->token_expires_at?->isPast()) {
            $this->refreshToken();
            $this->business->load('integrations');
            $integration = $this->integration();
        }

        $response = Http::withToken($integration->access_token)
            ->withHeader('X-JOBBER-GRAPHQL-VERSION', '2023-11-15')
            ->post(self::GQL_URL, [
                'query'     => $query,
                'variables' => $variables,
            ]);

        return $response->json('data') ?? [];
    }

    /**
     * Fetch a job. Returns null if not found.
     * Caller should check jobStatus === 'COMPLETED' before proceeding.
     */
    public function getJob(string $jobId): ?array
    {
        $data = $this->query('
            query GetJob($id: EncodedId!) {
                job(id: $id) {
                    jobStatus
                    client {
                        name
                        email
                        phones {
                            number
                            primary
                        }
                    }
                }
            }
        ', ['id' => $jobId]);

        return $data['job'] ?? null;
    }

    public function isConnected(): bool
    {
        return $this->integration()?->isConnected() ?? false;
    }
}
