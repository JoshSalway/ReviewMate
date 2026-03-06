<?php

use App\Jobs\RefreshGoogleStats;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\User;
use Illuminate\Support\Facades\Http;

// ── RefreshGoogleStats extended tests ────────────────────────────────────────
//
// The core job tests live in GoogleStatsTest.php. This file covers the
// firstOrCreate behaviour: when a google BusinessIntegration already exists
// (e.g. the business is connected to Google My Business), RefreshGoogleStats
// must update that integration's meta rather than creating a duplicate row.

test('RefreshGoogleStats updates meta on pre-existing google integration', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJexisting123',
    ]);

    // A google integration already exists (connected via OAuth)
    $existing = BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'google',
        'access_token' => 'real-access-token',
        'meta' => [
            'location_id' => 'accounts/123/locations/456',
            'rating' => 4.0,
            'review_count' => 80,
        ],
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
            'status' => 'OK',
            'result' => [
                'rating' => 4.9,
                'user_ratings_total' => 200,
            ],
        ], 200),
    ]);

    (new RefreshGoogleStats($business))->handle();

    // Must not create a second google integration row
    expect(BusinessIntegration::where('business_id', $business->id)->where('provider', 'google')->count())->toBe(1);

    $existing->refresh();

    // Meta must be updated with new stats
    expect((float) $existing->getMeta('rating'))->toBe(4.9);
    expect($existing->getMeta('review_count'))->toBe(200);

    // OAuth access_token must not be wiped by the stats update
    expect($existing->access_token)->toBe('real-access-token');

    // location_id must be preserved
    expect($existing->getMeta('location_id'))->toBe('accounts/123/locations/456');
});

test('RefreshGoogleStats creates new google integration when none exists', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJnew456',
    ]);

    expect(BusinessIntegration::where('business_id', $business->id)->where('provider', 'google')->exists())->toBeFalse();

    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
            'status' => 'OK',
            'result' => [
                'rating' => 4.5,
                'user_ratings_total' => 50,
            ],
        ], 200),
    ]);

    (new RefreshGoogleStats($business))->handle();

    $integration = BusinessIntegration::where('business_id', $business->id)->where('provider', 'google')->first();

    expect($integration)->not->toBeNull();
    expect((float) $integration->getMeta('rating'))->toBe(4.5);
    expect($integration->getMeta('review_count'))->toBe(50);
});

test('RefreshGoogleStats updates stats_updated_at timestamp on each run', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJtimestamp789',
    ]);

    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'google',
        'meta' => ['stats_updated_at' => now()->subHour()->toDateTimeString()],
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
            'status' => 'OK',
            'result' => ['rating' => 4.2, 'user_ratings_total' => 30],
        ], 200),
    ]);

    (new RefreshGoogleStats($business))->handle();

    $integration = BusinessIntegration::where('business_id', $business->id)->where('provider', 'google')->first();
    $updatedAt = $integration->getMeta('stats_updated_at');

    expect($updatedAt)->not->toBeNull();
    // stats_updated_at should be within the last minute
    expect(now()->diffInMinutes(\Carbon\Carbon::parse($updatedAt)) < 2)->toBeTrue();
});
