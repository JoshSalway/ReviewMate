<?php

use App\Jobs\RefreshGoogleStats;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('RefreshGoogleStats job updates business rating and review count when api returns data', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJtestplaceid12345',
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
            'status' => 'OK',
            'result' => [
                'rating' => 4.7,
                'user_ratings_total' => 142,
            ],
        ], 200),
    ]);

    (new RefreshGoogleStats($business))->handle();

    $integration = $business->integration('google');

    expect((float) $integration->getMeta('rating'))->toBe(4.7)
        ->and($integration->getMeta('review_count'))->toBe(142)
        ->and($integration->getMeta('stats_updated_at'))->not->toBeNull();
});

test('RefreshGoogleStats job skips when business has no google_place_id', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed_at' => now(),
        'google_place_id' => null,
    ]);

    Http::fake();

    (new RefreshGoogleStats($business))->handle();

    expect($business->integration('google'))->toBeNull();

    Http::assertNothingSent();
});

test('RefreshGoogleStats job handles api failure gracefully without throwing exception', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJtestplaceid12345',
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([], 500),
    ]);

    // Should not throw
    expect(fn () => (new RefreshGoogleStats($business))->handle())->not->toThrow(Exception::class);

    // Stats should remain unchanged (no integration created on failure)
    expect($business->integration('google'))->toBeNull();
});

test('RefreshGoogleStats job handles empty result gracefully', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJtestplaceid12345',
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
            'status' => 'ZERO_RESULTS',
            'result' => null,
        ], 200),
    ]);

    expect(fn () => (new RefreshGoogleStats($business))->handle())->not->toThrow(Exception::class);
});

test('dashboard passes google stats props to inertia', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
    ]);
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'google',
        'meta' => [
            'rating' => 4.7,
            'review_count' => 142,
            'stats_updated_at' => now()->subHour()->toDateTimeString(),
        ],
    ]);

    Review::factory()->count(2)->create([
        'business_id' => $business->id,
        'rating' => 5,
    ]);

    ReviewRequest::factory()->reviewed()->count(1)->create([
        'business_id' => $business->id,
        'customer_id' => $business->customers()->create(['name' => 'Test', 'email' => 'test@example.com'])->id,
    ]);

    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('googleRating')
            ->has('googleReviewCount')
            ->has('googleStatsUpdatedAt')
            ->where('googleRating', 4.7)
            ->where('googleReviewCount', 142)
        );
});

test('dashboard passes null google stats when business has no stats yet', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
    ]);

    Review::factory()->count(1)->create([
        'business_id' => $business->id,
        'rating' => 4,
    ]);

    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('googleRating', null)
            ->where('googleReviewCount', null)
            ->where('googleStatsUpdatedAt', null)
        );
});

test('GooglePlacesService getReviewStats returns correct data', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
            'status' => 'OK',
            'result' => [
                'rating' => 4.3,
                'user_ratings_total' => 87,
            ],
        ], 200),
    ]);

    $service = new \App\Services\GooglePlacesService;
    $stats = $service->getReviewStats('ChIJtestplaceid');

    expect($stats)->toEqual(['rating' => 4.3, 'review_count' => 87]);
});

test('GooglePlacesService getReviewStats returns null on api failure', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([], 503),
    ]);

    $service = new \App\Services\GooglePlacesService;
    $stats = $service->getReviewStats('ChIJtestplaceid');

    expect($stats)->toBeNull();
});
