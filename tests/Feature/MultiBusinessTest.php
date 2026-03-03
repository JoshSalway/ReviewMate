<?php

use App\Models\Business;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('user can switch to another of their businesses', function () {
    $business1 = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $business2 = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    $this->post("/businesses/{$business2->id}/switch")
        ->assertRedirect('/dashboard');

    expect($this->user->currentBusiness()->id)->toBe($business2->id);
});

test('user cannot switch to another users business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = Business::factory()->onboarded()->create(['user_id' => $otherUser->id]);

    $this->post("/businesses/{$otherBusiness->id}/switch")
        ->assertForbidden();
});

test('current business defaults to first business when no session', function () {
    $business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    expect($this->user->currentBusiness()->id)->toBe($business->id);
});

test('current business uses session when set', function () {
    $business1 = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $business2 = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    session(['current_business_id' => $business2->id]);

    expect($this->user->currentBusiness()->id)->toBe($business2->id);
});

test('user can add a second business', function () {
    Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    $this->post('/businesses', [
        'name' => 'Second Business',
        'type' => 'cafe',
    ])->assertRedirect('/onboarding/connect-google');

    expect($this->user->businesses()->count())->toBe(2);
});
