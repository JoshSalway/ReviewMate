<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;

// --- API Customers endpoint ---

test('authenticated user can list customers for their business via api', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    Customer::factory()->count(3)->create(['business_id' => $business->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$business->id}/customers")
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'phone']]])
        ->assertJsonCount(3, 'data');
});

test('user cannot list customers for another users business via api', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBusiness = Business::factory()->create(['user_id' => $otherUser->id]);
    Customer::factory()->count(3)->create(['business_id' => $otherBusiness->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$otherBusiness->id}/customers")
        ->assertForbidden();
});

// --- API Review Requests endpoint ---

test('authenticated user can list review requests for their business via api', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $customer = Customer::factory()->create(['business_id' => $business->id]);
    ReviewRequest::factory()->count(3)->create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$business->id}/review-requests")
        ->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJsonCount(3, 'data');
});

test('user cannot list review requests for another users business via api', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBusiness = Business::factory()->create(['user_id' => $otherUser->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$otherBusiness->id}/review-requests")
        ->assertForbidden();
});

test('authenticated user can create a review request via api', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $customer = Customer::factory()->create(['business_id' => $business->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/businesses/{$business->id}/review-requests", [
            'customer_id' => $customer->id,
            'channel' => 'email',
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'status', 'channel']]);

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'channel' => 'email',
        'source' => 'api',
    ]);
});

test('api review request creation requires customer_id and channel', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/businesses/{$business->id}/review-requests", [])
        ->assertUnprocessable();
});

test('api review request creation rejects invalid channel', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $customer = Customer::factory()->create(['business_id' => $business->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/businesses/{$business->id}/review-requests", [
            'customer_id' => $customer->id,
            'channel' => 'carrier_pigeon',
        ])
        ->assertUnprocessable();
});

test('api review request creation rejects customer from different business', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $otherBusiness = Business::factory()->create();
    $foreignCustomer = Customer::factory()->create(['business_id' => $otherBusiness->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/businesses/{$business->id}/review-requests", [
            'customer_id' => $foreignCustomer->id,
            'channel' => 'email',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Customer does not belong to this business.');
});

test('api review request creation rejects unsubscribed customer', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $customer = Customer::factory()->create([
        'business_id' => $business->id,
        'unsubscribed_at' => now()->subDay(),
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/businesses/{$business->id}/review-requests", [
            'customer_id' => $customer->id,
            'channel' => 'email',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Customer is unsubscribed.');
});

test('api review request creation enforces 30-day duplicate guard', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $customer = Customer::factory()->create(['business_id' => $business->id]);
    ReviewRequest::factory()->create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'created_at' => now()->subDays(5),
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/businesses/{$business->id}/review-requests", [
            'customer_id' => $customer->id,
            'channel' => 'email',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'A review request was already sent to this customer in the last 30 days.');
});

test('user cannot create review request for another users business via api', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBusiness = Business::factory()->create(['user_id' => $otherUser->id]);
    $customer = Customer::factory()->create(['business_id' => $otherBusiness->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/businesses/{$otherBusiness->id}/review-requests", [
            'customer_id' => $customer->id,
            'channel' => 'email',
        ])
        ->assertForbidden();
});

// --- API Reviews endpoint ---

test('authenticated user can list reviews for their business via api', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    Review::factory()->count(3)->create(['business_id' => $business->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$business->id}/reviews")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('user cannot list reviews for another users business via api', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBusiness = Business::factory()->create(['user_id' => $otherUser->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$otherBusiness->id}/reviews")
        ->assertForbidden();
});

// --- Token revocation ---

test('user can revoke all api tokens', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Issue a second token so there's 2
    $user->createToken('test2');

    $this->withToken($token)
        ->deleteJson('/api/v1/auth/tokens')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
