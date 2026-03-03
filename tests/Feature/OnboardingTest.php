<?php

use App\Models\Business;
use App\Models\User;

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
