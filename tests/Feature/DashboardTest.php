<?php

use App\Models\Business;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users with no business are redirected to onboarding', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/dashboard')->assertRedirect('/onboarding/business-type');
});

test('authenticated users with incomplete onboarding are redirected', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $this->get('/dashboard')->assertRedirect('/onboarding/business-type');
});

test('authenticated users with completed onboarding can view dashboard', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $this->get('/dashboard')->assertOk();
});

test('dashboard shows correct stats', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);

    Review::factory()->count(3)->create([
        'business_id' => $business->id,
        'rating' => 5,
    ]);

    ReviewRequest::factory()->reviewed()->count(2)->create([
        'business_id' => $business->id,
        'customer_id' => $business->customers()->create(['name' => 'Test', 'email' => 'test@example.com'])->id,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('stats')
            ->has('recentReviews')
        );
});

test('dashboard stats contain all required keys', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('stats.average_rating')
            ->has('stats.total_reviews_this_month')
            ->has('stats.requests_sent_this_month')
            ->has('stats.conversion_rate')
            ->has('stats.pending_replies')
        );
});
