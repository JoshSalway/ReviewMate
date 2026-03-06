<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);
});

test('tracking link marks request as opened and shows landing page', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
    ]);

    $this->get("/r/{$request->tracking_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reviews/landing'));

    $request->refresh();
    expect($request->status)->toBe('opened');
    expect($request->opened_at)->not->toBeNull();
});

test('tracking link does not downgrade already opened request', function () {
    $openedAt = now()->subHour();
    $request = ReviewRequest::factory()->opened()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'opened_at' => $openedAt,
    ]);

    $this->get("/r/{$request->tracking_token}")->assertOk();

    $request->refresh();
    expect($request->status)->toBe('opened');
});

test('invalid tracking token returns 404', function () {
    $this->get('/r/nonexistent-token')->assertNotFound();
});

test('review requests are created with a tracking token', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    expect($request->tracking_token)->not->toBeNull();
    expect(strlen($request->tracking_token))->toBe(36); // UUID length
});
