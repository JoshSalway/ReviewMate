<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    const API_BASE = 'https://maps.googleapis.com/maps/api/place';

    /**
     * Fetch current rating and review count for a business using its Place ID.
     * Returns ['rating' => 4.7, 'review_count' => 142] or null on failure.
     */
    public function getReviewStats(string $placeId): ?array
    {
        $response = Http::get(self::API_BASE.'/details/json', [
            'place_id' => $placeId,
            'fields' => 'rating,user_ratings_total',
            'key' => config('services.google.places_api_key'),
        ]);

        if ($response->failed()) {
            Log::error('Google Places API failed', ['status' => $response->status()]);

            return null;
        }

        $result = $response->json('result');

        if (! $result) {
            return null;
        }

        return [
            'rating' => $result['rating'] ?? null,
            'review_count' => $result['user_ratings_total'] ?? null,
        ];
    }

    /**
     * Search for a place by business name and address to get the Place ID.
     */
    public function findPlaceId(string $businessName, string $address = ''): ?string
    {
        $response = Http::get(self::API_BASE.'/findplacefromtext/json', [
            'input' => $businessName.($address ? ' '.$address : ''),
            'inputtype' => 'textquery',
            'fields' => 'place_id',
            'key' => config('services.google.places_api_key'),
        ]);

        return $response->json('candidates.0.place_id');
    }
}
