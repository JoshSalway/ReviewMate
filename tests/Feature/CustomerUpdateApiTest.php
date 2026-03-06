<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;

// ── API customer update/delete — gap documentation tests ─────────────────────
//
// NEXT_STEPS.md documents that the API has no endpoint to update or delete
// customers. These tests confirm that PUT/PATCH/DELETE to a customer route
// returns 404 or 405, making the absence explicit and detectable if a route
// is added in future.

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->create(['user_id' => $this->user->id]);
    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $this->token = $this->user->createToken('test')->plainTextToken;
});

test('PUT /api/v1/businesses/{business}/customers/{customer} does not exist — returns 404 or 405', function () {
    $response = $this->withToken($this->token)
        ->putJson(
            "/api/v1/businesses/{$this->business->id}/customers/{$this->customer->id}",
            ['name' => 'Updated Name'],
        );

    // Route does not exist, so either 404 (no route) or 405 (wrong method) is acceptable.
    expect($response->status())->toBeIn([404, 405]);
});

test('PATCH /api/v1/businesses/{business}/customers/{customer} does not exist — returns 404 or 405', function () {
    $response = $this->withToken($this->token)
        ->patchJson(
            "/api/v1/businesses/{$this->business->id}/customers/{$this->customer->id}",
            ['name' => 'Patched Name'],
        );

    expect($response->status())->toBeIn([404, 405]);
});

test('DELETE /api/v1/businesses/{business}/customers/{customer} does not exist — returns 404 or 405', function () {
    $response = $this->withToken($this->token)
        ->deleteJson(
            "/api/v1/businesses/{$this->business->id}/customers/{$this->customer->id}",
        );

    expect($response->status())->toBeIn([404, 405]);
});

test('GET /api/v1/businesses/{business}/customers lists customers but offers no individual customer GET route', function () {
    // The list endpoint exists; a single-customer show route does not.
    $this->withToken($this->token)
        ->getJson("/api/v1/businesses/{$this->business->id}/customers")
        ->assertOk();

    $individual = $this->withToken($this->token)
        ->getJson("/api/v1/businesses/{$this->business->id}/customers/{$this->customer->id}");

    expect($individual->status())->toBeIn([404, 405]);
});
