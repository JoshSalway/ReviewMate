<?php

use App\Models\Business;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access quick send page', function () {
    auth()->logout();
    $this->get('/quick-send')->assertRedirect('/login');
});

test('users can view the quick send page', function () {
    $this->get('/quick-send')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('quick-send'));
});

test('users can send a quick review request', function () {
    $this->post('/quick-send', [
        'name' => 'Sarah Johnson',
        'email' => 'sarah@example.com',
        'channel' => 'email',
    ])->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'business_id' => $this->business->id,
        'name' => 'Sarah Johnson',
        'email' => 'sarah@example.com',
    ]);

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $this->business->id,
        'status' => 'sent',
        'channel' => 'email',
    ]);
});

test('quick send creates a new customer if they do not exist', function () {
    expect($this->business->customers()->count())->toBe(0);

    $this->post('/quick-send', [
        'name' => 'New Customer',
        'email' => 'new@example.com',
        'channel' => 'email',
    ]);

    expect($this->business->customers()->count())->toBe(1);
});

test('quick send channel is required', function () {
    $this->post('/quick-send', [
        'name' => 'Test',
        'email' => 'test@example.com',
    ])->assertSessionHasErrors('channel');
});
