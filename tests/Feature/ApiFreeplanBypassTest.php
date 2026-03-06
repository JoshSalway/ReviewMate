<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// ── API + web free-plan monthly limit tests ───────────────────────────────────
//
// Both the web ReviewRequestController (POST /requests) and the API
// ReviewRequestController (POST /api/v1/businesses/{id}/review-requests) enforce
// the 10/month free-plan cap. These tests verify the limit is enforced consistently
// across both surfaces, and that paid/trial users are not limited.

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create(); // free plan by default
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $this->token = $this->user->createToken('test')->plainTextToken;
});

test('free plan user cannot exceed 10 review requests per month via the web UI', function () {
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'created_at' => now(),
    ]);

    // Create a distinct new customer so resend guard doesn't mask the plan limit
    $newCustomer = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->actingAs($this->user)
        ->post('/requests', [
            'customer_id' => $newCustomer->id,
            'channel' => 'email',
        ])
        ->assertSessionHas('error');
});

test('free plan user cannot exceed 10 review requests per month via the API', function () {
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'created_at' => now(),
    ]);

    $newCustomer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/v1/businesses/{$this->business->id}/review-requests", [
            'customer_id' => $newCustomer->id,
            'channel' => 'email',
        ]);

    // API enforces the cap with 422 (consistent with web error behaviour)
    expect($response->status())->toBe(422);
    expect($response->json('message'))->toContain('10 review requests per month');
});

test('pro plan user is not blocked by the monthly limit on the web UI', function () {
    $this->user->update(['trial_ends_at' => now()->addMonth()]); // simulate active trial / paid

    ReviewRequest::factory()->count(15)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'created_at' => now(),
    ]);

    // Create a distinct customer so the resend guard doesn't block this request
    $newCustomer = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->actingAs($this->user)
        ->post('/requests', [
            'customer_id' => $newCustomer->id,
            'channel' => 'email',
        ])
        ->assertSessionMissing('error');
});

test('pro plan user is not blocked by the monthly limit on the API', function () {
    $this->user->update(['trial_ends_at' => now()->addMonth()]);

    ReviewRequest::factory()->count(15)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'created_at' => now(),
    ]);

    $newCustomer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/v1/businesses/{$this->business->id}/review-requests", [
            'customer_id' => $newCustomer->id,
            'channel' => 'email',
        ]);

    // Paid user should succeed (201) — not blocked by plan limit
    expect($response->status())->toBe(201);
});
