<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class GoogleBusinessProfileService
{
    private const BASE_URL = 'https://mybusinessaccountmanagement.googleapis.com/v1';
    private const REVIEWS_BASE_URL = 'https://mybusiness.googleapis.com/v4';

    public function fetchReviews(Business $business, int $pageSize = 50): array
    {
        $token = $this->getAccessToken($business);
        $locationId = $business->google_location_id;

        $response = Http::withToken($token)
            ->get("{$this->REVIEWS_BASE_URL}/{$locationId}/reviews", [
                'pageSize' => $pageSize,
            ]);

        $response->throw();

        return $response->json('reviews', []);
    }

    public function postReply(Business $business, string $reviewName, string $reply): void
    {
        $token = $this->getAccessToken($business);

        Http::withToken($token)
            ->put("{$this->REVIEWS_BASE_URL}/{$reviewName}/reply", [
                'comment' => $reply,
            ])
            ->throw();
    }

    public function deleteReply(Business $business, string $reviewName): void
    {
        $token = $this->getAccessToken($business);

        Http::withToken($token)
            ->delete("{$this->REVIEWS_BASE_URL}/{$reviewName}/reply")
            ->throw();
    }

    public function discoverAccountAndLocation(Business $business): void
    {
        $token = $this->getAccessToken($business);

        // Fetch accounts
        $accountsResponse = Http::withToken($token)
            ->get(self::BASE_URL . '/accounts')
            ->throw();

        $accounts = $accountsResponse->json('accounts', []);

        if (empty($accounts)) {
            return;
        }

        $accountName = $accounts[0]['name'] ?? null;

        if (! $accountName) {
            return;
        }

        // Fetch locations for the first account
        $locationsResponse = Http::withToken($token)
            ->get(self::BASE_URL . "/{$accountName}/locations", [
                'readMask' => 'name,title',
            ]);

        if ($locationsResponse->failed()) {
            $business->update(['google_account_id' => $accountName]);
            return;
        }

        $locations = $locationsResponse->json('locations', []);
        $locationName = $locations[0]['name'] ?? null;

        $business->update([
            'google_account_id' => $accountName,
            'google_location_id' => $locationName,
        ]);
    }

    private function getAccessToken(Business $business): string
    {
        if ($business->google_token_expires_at && now()->gte($business->google_token_expires_at)) {
            $this->refreshToken($business);
            $business->refresh();
        }

        return $business->google_access_token;
    }

    private function refreshToken(Business $business): void
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $business->google_refresh_token,
            'grant_type'    => 'refresh_token',
        ])->throw();

        $data = $response->json();

        $business->update([
            'google_access_token'     => $data['access_token'],
            'google_token_expires_at' => now()->addSeconds($data['expires_in'] - 60)->toDateTimeString(),
        ]);
    }
}
