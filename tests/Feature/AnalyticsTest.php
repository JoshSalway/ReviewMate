<?php

use App\Models\Business;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access analytics page', function () {
    auth()->logout();
    $this->get('/analytics')->assertRedirect('/login');
});

test('user with completed onboarding can view analytics', function () {
    $this->get('/analytics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('analytics')
            ->has('businesses')
            ->has('totals')
            ->has('can_see_all')
        );
});

test('user without completed onboarding is redirected', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/analytics')
        ->assertRedirect(route('onboarding.business-type'));
});

test('analytics totals reflect review and request counts', function () {
    Review::factory()->count(5)->create(['business_id' => $this->business->id, 'rating' => 5]);
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => \App\Models\Customer::factory()->create(['business_id' => $this->business->id])->id,
    ]);

    $this->get('/analytics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('totals.total_reviews', 5)
            ->where('totals.requests_sent', 10)
        );
});

test('free plan user can only see current business in analytics', function () {
    $otherBusiness = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    $this->get('/analytics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can_see_all', false)
            ->has('businesses', 1)
        );
});

test('admin user can see all businesses in analytics', function () {
    $this->user->update(['is_admin' => true]);
    Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    $this->get('/analytics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can_see_all', true)
            ->has('businesses', 2)
        );
});

test('analytics shows pending reply count correctly', function () {
    // Review with google link and no reply — needs reply
    Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/abc',
        'google_reply' => null,
    ]);
    // Review without google link — does not need reply
    Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_name' => null,
    ]);

    $this->get('/analytics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('totals.pending_replies', 1)
        );
});
