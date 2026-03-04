<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Http;

class TimelyService
{
    const AUTH_URL  = 'https://identity.gettimely.com/connect/authorize';
    const TOKEN_URL = 'https://identity.gettimely.com/connect/token';
    const API_BASE  = 'https://api.gettimely.com';

    public function __construct(protected Business $business) {}

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => config('services.timely.client_id'),
            'redirect_uri'  => route('integrations.timely.callback'),
            'scope'         => 'appointments clients',
            'state'         => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.timely.client_id'),
            'client_secret' => config('services.timely.client_secret'),
            'redirect_uri'  => route('integrations.timely.callback'),
            'code'          => $code,
        ]);

        return $response->json() ?? [];
    }

    public function getClient(int $accountId, int $clientId): ?array
    {
        $response = Http::withToken($this->business->timely_access_token)
            ->get(self::API_BASE . "/{$accountId}/clients/{$clientId}");

        return $response->json() ?? null;
    }

    public function isConnected(): bool
    {
        return filled($this->business->timely_access_token);
    }
}
