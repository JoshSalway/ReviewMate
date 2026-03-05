<?php

use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\User;
use App\Services\GoogleBusinessProfileService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('guests are redirected from onboarding', function () {
    auth()->logout();
    $this->get('/onboarding/business-type')->assertRedirect('/login');
});

test('users with completed onboarding are redirected from business-type step', function () {
    Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    $this->get('/onboarding/business-type')->assertRedirect('/dashboard');
});

test('users can access business-type onboarding step', function () {
    $this->get('/onboarding/business-type')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('onboarding/business-type'));
});

test('users can submit business type and are redirected to next step', function () {
    $this->post('/onboarding/business-type', [
        'name' => 'My Cafe',
        'type' => 'cafe',
        'owner_name' => 'John Smith',
    ])->assertRedirect('/onboarding/connect-google');

    $this->assertDatabaseHas('businesses', [
        'user_id' => $this->user->id,
        'name' => 'My Cafe',
        'type' => 'cafe',
    ]);
});

test('business type submission requires name and type', function () {
    $this->post('/onboarding/business-type', [])
        ->assertSessionHasErrors(['name', 'type']);
});

test('connect-google redirects to business-type if no business exists', function () {
    $this->get('/onboarding/connect-google')
        ->assertRedirect('/onboarding/business-type');
});

test('users can submit google place id', function () {
    $business = Business::factory()->create(['user_id' => $this->user->id]);

    $this->post('/onboarding/connect-google', [
        'google_place_id' => 'ChIJtesting123',
    ])->assertRedirect('/onboarding/select-template');

    $this->assertDatabaseHas('businesses', [
        'id' => $business->id,
        'google_place_id' => 'ChIJtesting123',
    ]);
});

test('select-template step completes onboarding', function () {
    $business = Business::factory()->create([
        'user_id' => $this->user->id,
        'google_place_id' => 'ChIJtesting123',
    ]);

    $this->post('/onboarding/select-template')
        ->assertRedirect('/dashboard');

    expect($business->fresh()->isOnboardingComplete())->toBeTrue();
});

test('connect-google passes isGoogleConnected false when not connected', function () {
    Business::factory()->create(['user_id' => $this->user->id]);

    $this->get('/onboarding/connect-google')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/connect-google')
            ->where('isGoogleConnected', false)
            ->where('locations', [])
        );
});

test('connect-google passes discovered locations when google is connected', function () {
    $business = Business::factory()->create(['user_id' => $this->user->id]);
    BusinessIntegration::create([
        'business_id'      => $business->id,
        'provider'         => 'google',
        'access_token'     => 'fake-token',
        'refresh_token'    => 'fake-refresh',
        'token_expires_at' => now()->addHour(),
        'meta'             => ['account_id' => 'accounts/123', 'location_id' => 'accounts/123/locations/456'],
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('listLocationsWithPlaceIds')->once()->andReturn([
        ['name' => 'accounts/123/locations/456', 'title' => 'My Cafe', 'place_id' => 'ChIJtesting123'],
    ]);
    app()->instance(GoogleBusinessProfileService::class, $service);

    $this->get('/onboarding/connect-google')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/connect-google')
            ->where('isGoogleConnected', true)
            ->where('locations.0.place_id', 'ChIJtesting123')
        );
});

test('connect-google falls back to empty locations when google api fails', function () {
    $business = Business::factory()->create(['user_id' => $this->user->id]);
    BusinessIntegration::create([
        'business_id'      => $business->id,
        'provider'         => 'google',
        'access_token'     => 'fake-token',
        'refresh_token'    => 'fake-refresh',
        'token_expires_at' => now()->addHour(),
        'meta'             => ['account_id' => 'accounts/123', 'location_id' => 'accounts/123/locations/456'],
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('listLocationsWithPlaceIds')->once()->andThrow(new \Exception('API error'));
    app()->instance(GoogleBusinessProfileService::class, $service);

    $this->get('/onboarding/connect-google')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isGoogleConnected', true)
            ->where('locations', [])
        );
});

test('google oauth callback redirects to onboarding when not complete', function () {
    $business = Business::factory()->create(['user_id' => $this->user->id]);
    session(['current_business_id' => $business->id]);

    // Mock Socialite
    $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $googleUser->token = 'access-token';
    $googleUser->refreshToken = 'refresh-token';
    $googleUser->expiresIn = 3600;

    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('discoverAccountAndLocation')->once();
    app()->instance(GoogleBusinessProfileService::class, $service);

    $this->get('/google/callback')->assertRedirect('/onboarding/connect-google');
});

test('google oauth callback redirects to settings when onboarding is complete', function () {
    $business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    session(['current_business_id' => $business->id]);

    $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $googleUser->token = 'access-token';
    $googleUser->refreshToken = 'refresh-token';
    $googleUser->expiresIn = 3600;

    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('discoverAccountAndLocation')->once();
    app()->instance(GoogleBusinessProfileService::class, $service);

    $this->get('/google/callback')->assertRedirect('/settings/business');
});

test('onboarding creates default email templates', function () {
    $business = Business::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'cafe',
        'google_place_id' => 'ChIJtesting123',
    ]);

    $this->post('/onboarding/select-template');

    $this->assertDatabaseHas('email_templates', [
        'business_id' => $business->id,
        'type' => 'request',
    ]);
    $this->assertDatabaseHas('email_templates', [
        'business_id' => $business->id,
        'type' => 'followup',
    ]);
});
