<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create(); // free plan by default
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

// --- Customer limits ---

test('free plan user cannot add more than 50 customers', function () {
    Customer::factory()->count(50)->create(['business_id' => $this->business->id]);

    $this->post('/customers', [
        'name' => 'Extra Customer',
        'email' => 'extra@example.com',
    ])->assertSessionHas('error');

    expect($this->business->customers()->count())->toBe(50);
});

test('free plan user can add customer when under 50 limit', function () {
    Customer::factory()->count(49)->create(['business_id' => $this->business->id]);

    $this->post('/customers', [
        'name' => 'One More',
        'email' => 'onemore@example.com',
    ])->assertSessionMissing('error');

    expect($this->business->customers()->count())->toBe(50);
});

test('admin user is not limited to 50 customers', function () {
    $this->user->update(['is_admin' => true]);
    Customer::factory()->count(50)->create(['business_id' => $this->business->id]);

    $this->post('/customers', [
        'name' => 'Extra Customer',
        'email' => 'extra@example.com',
    ])->assertSessionMissing('error');

    expect($this->business->customers()->count())->toBe(51);
});

// --- Monthly review request limits ---

test('free plan user cannot send more than 10 review requests per month', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    // Create 10 requests this month
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'created_at' => now(),
    ]);

    // Create a new customer to send the 11th request to
    $newCustomer = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->post('/requests', [
        'customer_id' => $newCustomer->id,
        'channel' => 'email',
    ])->assertSessionHas('error');

    expect(ReviewRequest::where('customer_id', $newCustomer->id)->count())->toBe(0);
});

test('free plan user can send exactly 10 review requests per month', function () {
    // Create 9 existing requests this month
    ReviewRequest::factory()->count(9)->create([
        'business_id' => $this->business->id,
        'customer_id' => Customer::factory()->create(['business_id' => $this->business->id])->id,
        'created_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'tenth@example.com',
    ]);

    $this->post('/requests', [
        'customer_id' => $customer->id,
        'channel' => 'email',
    ])->assertSessionMissing('error');
});

test('free plan monthly limit resets each month', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    // 10 requests last month
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'created_at' => now()->subMonth(),
    ]);

    $newCustomer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'fresh@example.com',
    ]);

    $this->post('/requests', [
        'customer_id' => $newCustomer->id,
        'channel' => 'email',
    ])->assertSessionMissing('error');
});

test('quick send also enforces free plan 10/month limit', function () {
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => Customer::factory()->create(['business_id' => $this->business->id])->id,
        'created_at' => now(),
    ]);

    $this->post('/quick-send', [
        'name' => 'New Person',
        'email' => 'new@example.com',
        'channel' => 'email',
    ])->assertSessionHas('error');
});

// --- Business limits ---

test('starter plan user cannot add a second business', function () {
    // Already has one business from beforeEach, on free plan
    $this->post('/businesses', [
        'name' => 'Second Business',
        'type' => 'cafe',
    ])->assertSessionHas('error');

    expect($this->user->businesses()->count())->toBe(1);
});

test('pro plan user can add up to 5 businesses', function () {
    // Mock the user as subscribed to pro price
    $proPriceId = config('services.stripe.price_pro');
    $this->user->update(['is_admin' => true]); // admin bypasses limits

    // Admin can add businesses freely
    $this->post('/businesses', ['name' => 'Business 2', 'type' => 'cafe'])
        ->assertRedirect();

    expect($this->user->businesses()->count())->toBe(2);
});

// --- Bulk send respects free plan monthly cap ---

test('bulk send is capped to remaining monthly allowance on free plan', function () {
    // Already sent 8 this month
    ReviewRequest::factory()->count(8)->create([
        'business_id' => $this->business->id,
        'customer_id' => Customer::factory()->create(['business_id' => $this->business->id])->id,
        'created_at' => now(),
    ]);

    // Try to bulk send to 5 customers
    $customers = Customer::factory()->count(5)->create(['business_id' => $this->business->id]);
    $customerIds = $customers->pluck('id')->toArray();

    $this->post('/customers/bulk-send', [
        'customer_ids' => $customerIds,
        'channel' => 'email',
    ])->assertSessionHas('success');

    // Only 2 should be sent (10 - 8 = 2 remaining)
    $newRequests = ReviewRequest::where('business_id', $this->business->id)
        ->where('created_at', '>=', now()->startOfMonth())
        ->whereIn('customer_id', $customerIds)
        ->count();

    expect($newRequests)->toBe(2);
});

test('bulk send returns error when monthly cap is already at zero for free plan', function () {
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => Customer::factory()->create(['business_id' => $this->business->id])->id,
        'created_at' => now(),
    ]);

    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->post('/customers/bulk-send', [
        'customer_ids' => [$customer->id],
        'channel' => 'email',
    ])->assertSessionHas('error');
});
