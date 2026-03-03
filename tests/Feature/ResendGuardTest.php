<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'test@example.com',
    ]);
    $this->actingAs($this->user);
});

test('cannot send duplicate request within 30 days', function () {
    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
        'created_at' => now()->subDays(5),
    ]);

    $this->post('/requests', [
        'customer_id' => $this->customer->id,
        'channel' => 'email',
    ])->assertSessionHas('error');

    expect(ReviewRequest::where('customer_id', $this->customer->id)->count())->toBe(1);
});

test('can send request after 30 days have passed', function () {
    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
        'created_at' => now()->subDays(31),
    ]);

    $this->post('/requests', [
        'customer_id' => $this->customer->id,
        'channel' => 'email',
    ])->assertSessionMissing('error');

    expect(ReviewRequest::where('customer_id', $this->customer->id)->count())->toBe(2);
});

test('can send new request when previous was no_response', function () {
    ReviewRequest::factory()->noResponse()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'created_at' => now()->subDays(5),
    ]);

    $this->post('/requests', [
        'customer_id' => $this->customer->id,
        'channel' => 'email',
    ])->assertSessionMissing('error');

    expect(ReviewRequest::where('customer_id', $this->customer->id)->count())->toBe(2);
});

test('bulk send skips customers with recent requests', function () {
    $other = Customer::factory()->create(['business_id' => $this->business->id]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
        'created_at' => now()->subDays(5),
    ]);

    $this->post('/customers/bulk-send', [
        'customer_ids' => [$this->customer->id, $other->id],
        'channel' => 'email',
    ])->assertSessionHas('success');

    // Only the new customer should get a request
    expect(ReviewRequest::where('customer_id', $other->id)->count())->toBe(1);
    expect(ReviewRequest::where('customer_id', $this->customer->id)->count())->toBe(1);
});

test('hasRecentRequest returns false for different businesses', function () {
    $otherBusiness = Business::factory()->onboarded()->create();

    ReviewRequest::factory()->create([
        'business_id' => $otherBusiness->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
        'created_at' => now()->subDays(5),
    ]);

    expect(ReviewRequest::hasRecentRequest($this->business->id, $this->customer->id))->toBeFalse();
});
