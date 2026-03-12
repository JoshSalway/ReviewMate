<?php

use App\Models\Business;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access billing page', function () {
    auth()->logout();
    $this->get('/settings/billing')->assertRedirect('/login');
});

test('free plan user can view billing page', function () {
    $this->get('/settings/billing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/billing')
            ->where('plan', null)
            ->where('onFreePlan', true)
            ->where('isAdmin', false)
        );
});

test('admin user sees admin plan on billing page', function () {
    $this->user->update(['is_admin' => true]);

    $this->get('/settings/billing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('plan', 'admin')
            ->where('isAdmin', true)
            ->where('onFreePlan', false)
        );
});

test('billing page exposes stripe price ids', function () {
    $this->get('/settings/billing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('prices.starter')
            ->has('prices.pro')
        );
});

test('admin cannot subscribe — returns error', function () {
    $this->user->update(['is_admin' => true]);

    $this->post('/settings/billing/subscribe', ['price' => 'price_starter'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('subscribe requires price field', function () {
    $this->post('/settings/billing/subscribe', [])
        ->assertSessionHasErrors('price');
});

test('billing portal requires authentication', function () {
    auth()->logout();
    $this->post('/settings/billing/portal')->assertRedirect('/login');
});

test('subscribe requires authentication', function () {
    auth()->logout();
    $this->post('/settings/billing/subscribe', ['price' => 'price_starter'])
        ->assertRedirect('/login');
});

test('billing page returns 200 for authenticated users', function () {
    $this->get('/settings/billing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/billing')
        );
});
