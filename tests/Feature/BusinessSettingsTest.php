<?php

use App\Models\Business;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access business settings', function () {
    auth()->logout();
    $this->get('/settings/business')->assertRedirect('/login');
});

test('users can view business settings', function () {
    $this->get('/settings/business')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/business'));
});

test('users can update business settings', function () {
    $this->put('/settings/business', [
        'name' => 'Updated Business Name',
        'type' => 'salon',
        'google_place_id' => 'ChIJupdated',
        'owner_name' => 'Jane Owner',
        'phone' => '0400111222',
    ])->assertRedirect();

    $this->assertDatabaseHas('businesses', [
        'id' => $this->business->id,
        'name' => 'Updated Business Name',
        'type' => 'salon',
    ]);
});

test('business name is required for settings update', function () {
    $this->put('/settings/business', [
        'type' => 'salon',
    ])->assertSessionHasErrors('name');
});

test('business type must be valid', function () {
    $this->put('/settings/business', [
        'name' => 'My Business',
        'type' => 'invalid_type',
    ])->assertSessionHasErrors('type');
});

test('google place id is saved when updating business settings', function () {
    $this->put('/settings/business', [
        'name' => $this->business->name,
        'type' => $this->business->type,
        'google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
    ])->assertRedirect();

    $this->assertDatabaseHas('businesses', [
        'id' => $this->business->id,
        'google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
    ]);
});

test('google place id can be cleared', function () {
    $this->business->update(['google_place_id' => 'ChIJexisting']);

    $this->put('/settings/business', [
        'name' => $this->business->name,
        'type' => $this->business->type,
        'google_place_id' => '',
    ])->assertRedirect();

    $this->assertDatabaseHas('businesses', [
        'id' => $this->business->id,
        'google_place_id' => null,
    ]);
});

test('settings page exposes google place id to frontend', function () {
    $this->business->update(['google_place_id' => 'ChIJtest123']);

    $this->get('/settings/business')->assertInertia(fn ($page) => $page
        ->where('business.google_place_id', 'ChIJtest123')
    );
});
