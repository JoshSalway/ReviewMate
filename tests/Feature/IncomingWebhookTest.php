<?php

use App\Jobs\ProcessIncomingWebhook;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
        'webhook_token' => Str::random(40),
    ]);
    $this->actingAs($this->user);
});

// --- Handle endpoint ---

test('valid token with email queues job', function () {
    Queue::fake();

    $response = $this->postJson("/webhooks/incoming/{$this->business->webhook_token}", [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    Queue::assertPushed(ProcessIncomingWebhook::class, function ($job) {
        return $job->business->id === $this->business->id
            && $job->data['email'] === 'jane@example.com';
    });
});

test('valid token with phone queues job', function () {
    Queue::fake();

    $response = $this->postJson("/webhooks/incoming/{$this->business->webhook_token}", [
        'name' => 'Bob Jones',
        'phone' => '0412345678',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'queued']);

    Queue::assertPushed(ProcessIncomingWebhook::class, function ($job) {
        return $job->business->id === $this->business->id
            && $job->data['phone'] === '0412345678';
    });
});

test('invalid token returns 401', function () {
    Queue::fake();

    $response = $this->postJson('/webhooks/incoming/invalid-token-here', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Invalid token']);

    Queue::assertNothingPushed();
});

test('missing both email and phone returns 422', function () {
    Queue::fake();

    $response = $this->postJson("/webhooks/incoming/{$this->business->webhook_token}", [
        'name' => 'No Contact',
        'trigger' => 'job_completed',
    ]);

    $response->assertStatus(422)
        ->assertJson(['error' => 'At least one of email or phone is required']);

    Queue::assertNothingPushed();
});

test('optional trigger field is accepted', function () {
    Queue::fake();

    $response = $this->postJson("/webhooks/incoming/{$this->business->webhook_token}", [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'trigger' => 'job_completed',
    ]);

    $response->assertStatus(200);

    Queue::assertPushed(ProcessIncomingWebhook::class, function ($job) {
        return $job->data['trigger'] === 'job_completed';
    });
});

// --- 90-day dedup (job logic) ---

test('90-day dedup prevents review request when recent request exists', function () {
    // Create customer with a recent review request
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'jane@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'created_at' => now()->subDays(10),
        'status' => 'sent',
    ]);

    $initialCount = ReviewRequest::where('business_id', $this->business->id)->count();

    // Dispatch the job synchronously
    $job = new \App\Jobs\ProcessIncomingWebhook($this->business, [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);
    $job->handle();

    // No new review request should have been created
    expect(ReviewRequest::where('business_id', $this->business->id)->count())
        ->toBe($initialCount);
});

// --- Regenerate token ---

test('regenerate changes the webhook token', function () {
    $oldToken = $this->business->webhook_token;

    $response = $this->post('/settings/integrations/webhook/regenerate');

    $response->assertRedirect();

    $this->business->refresh();
    expect($this->business->webhook_token)->not->toBe($oldToken);
    expect(strlen($this->business->webhook_token))->toBe(40);
});

test('regenerate requires authentication', function () {
    auth()->logout();

    $response = $this->post('/settings/integrations/webhook/regenerate');

    $response->assertRedirect(); // redirects to login
});

// --- Business model auto-generates token ---

test('webhook token is auto-generated on business creation', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);

    expect($business->webhook_token)->not->toBeNull();
    expect(strlen($business->webhook_token))->toBe(40);
});
