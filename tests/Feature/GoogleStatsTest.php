<?php

use App\Jobs\RefreshGoogleStats;
use App\Models\Business;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Services\GooglePlacesService;
use Illuminate\Support\Facades\Http;

test('RefreshGoogleStats job updates business rating and review count when api returns data', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJtestplaceid12345',
        'google_rating' => null,
        'google_review_count' => null,
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

    $business->refresh();

    expect((float) $business->google_rating)->toBe(4.7)
        ->and($business->google_review_count)->toBe(142)
        ->and($business->google_stats_updated_at)->not->toBeNull();
});

test('RefreshGoogleStats job skips when business has no google_place_id', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed_at' => now(),
        'google_place_id' => null,
        'google_rating' => null,
        'google_review_count' => null,
    ]);

    Http::fake();

    (new RefreshGoogleStats($business))->handle();

    $business->refresh();

    expect($business->google_rating)->toBeNull()
        ->and($business->google_review_count)->toBeNull()
        ->and($business->google_stats_updated_at)->toBeNull();

    Http::assertNothingSent();
});

test('RefreshGoogleStats job handles api failure gracefully without throwing exception', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJtestplaceid12345',
        'google_rating' => null,
        'google_review_count' => null,
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/place/details/json*' => Http::response([], 500),
    ]);

    // Should not throw
    expect(fn () => (new RefreshGoogleStats($business))->handle())->not->toThrow(Exception::class);

    $business->refresh();

    // Stats should remain unchanged
    expect($business->google_rating)->toBeNull()
        ->and($business->google_review_count)->toBeNull()
        ->and($business->google_stats_updated_at)->toBeNull();
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
        'google_rating' => 4.7,
        'google_review_count' => 142,
        'google_stats_updated_at' => now()->subHour(),
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
        'google_rating' => null,
        'google_review_count' => null,
        'google_stats_updated_at' => null,
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
