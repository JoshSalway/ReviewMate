<?php

use App\Models\Business;
use App\Models\User;

test('guests are redirected to login when accessing qr-code page', function () {
    $this->get('/qr-code')->assertRedirect('/login');
});

test('authenticated user with onboarded business can access qr-code page', function () {
    $user = User::factory()->create();
    Business::factory()->onboarded()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $this->get('/qr-code')->assertOk();
});

test('qr-code page renders via inertia with business data', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'name' => 'Test Cafe',
    ]);
    $this->actingAs($user);

    $this->get('/qr-code')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('qr-code')
            ->has('business')
            ->where('business.name', 'Test Cafe')
            ->has('business.google_review_url')
        );
});

test('qr-code page includes google review url when place id is set', function () {
    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create([
        'user_id' => $user->id,
        'google_place_id' => 'ChIJtestplaceid12345',
    ]);
    $this->actingAs($user);

    $this->get('/qr-code')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('business.google_review_url', 'https://search.google.com/local/writereview?placeid=ChIJtestplaceid12345')
        );
});

test('qr-code page shows google search fallback url when no place id is set', function () {
    $user = User::factory()->create();
    // Create business with onboarding done but explicitly no place id
    $business = Business::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Plumbing Co',
        'onboarding_completed_at' => now(),
        'google_place_id' => null,
    ]);
    $this->actingAs($user);

    $expectedUrl = 'https://www.google.com/search?q='.urlencode('Test Plumbing Co reviews');

    $this->get('/qr-code')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('business.google_review_url', $expectedUrl)
        );
});
