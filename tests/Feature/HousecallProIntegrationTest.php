<?php

use App\Jobs\ProcessHousecallProJobCompletion;
use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

// ─── Webhook ────────────────────────────────────────────────────────────────

test('housecallpro webhook queues job for job.completed event', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $jobData = [
        'id' => 'hcp-job-123',
        'customer' => [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'mobile_number' => '+61400000002',
        ],
    ];

    $response = $this->postJson("/webhooks/housecallpro/{$business->uuid}", [
        'event_action' => 'job.completed',
        'event_resource' => $jobData,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);
    Queue::assertPushed(ProcessHousecallProJobCompletion::class, function ($job) use ($business, $jobData) {
        return $job->business->id === $business->id
            && $job->jobData['id'] === $jobData['id'];
    });
});

test('housecallpro webhook ignores non-job.completed events', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/housecallpro/{$business->uuid}", [
        'event_action' => 'job.created',
        'event_resource' => ['id' => 'hcp-job-456'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessHousecallProJobCompletion::class);
});

test('housecallpro webhook returns 404 for unknown business uuid', function () {
    $response = $this->postJson('/webhooks/housecallpro/00000000-0000-0000-0000-000000000000', [
        'event_action' => 'job.completed',
        'event_resource' => ['id' => 'hcp-job-789'],
    ]);

    $response->assertStatus(404);
});

test('housecallpro webhook returns auto_send_disabled when auto send is disabled', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
        'auto_send_reviews' => false,
    ]);

    $response = $this->postJson("/webhooks/housecallpro/{$business->uuid}", [
        'event_action' => 'job.completed',
        'event_resource' => [
            'id' => 'hcp-job-123',
            'customer' => ['first_name' => 'Bob', 'email' => 'bob@example.com'],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'auto_send_disabled']);
    Queue::assertNotPushed(ProcessHousecallProJobCompletion::class);
});

test('housecallpro webhook returns 400 when payload has no job data', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/housecallpro/{$business->uuid}", [
        'event_action' => 'job.completed',
        // no event_resource or data key
    ]);

    $response->assertStatus(400);
    $response->assertJson(['status' => 'no job data']);
    Queue::assertNotPushed(ProcessHousecallProJobCompletion::class);
});

// ─── Controller actions ──────────────────────────────────────────────────────

test('housecallpro disconnect removes integration and redirects', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
    ]);

    $this->actingAs($user)
        ->post('/integrations/housecallpro/disconnect')
        ->assertRedirect('/settings/integrations');

    expect($business->integration('housecallpro'))->toBeNull();
});

test('housecallpro toggleAutoSend flips auto_send_reviews', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $integration = BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $this->actingAs($user)->post('/integrations/housecallpro/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeFalse();

    $this->actingAs($user)->post('/integrations/housecallpro/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeTrue();
});

test('housecallpro connect redirects unauthenticated users to login', function () {
    $response = $this->get('/integrations/housecallpro/connect');
    $response->assertRedirect('/login');
});

// ─── Integrations page ───────────────────────────────────────────────────────

test('integrations page shows housecallProConnected true when token present', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
    ]);

    $this->actingAs($user)
        ->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('housecallProConnected', true)
        );
});

// ─── ProcessHousecallProJobCompletion job ────────────────────────────────────

test('ProcessHousecallProJobCompletion creates customer and queues email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $jobData = [
        'id' => 'hcp-job-999',
        'customer' => [
            'first_name' => 'Carol',
            'last_name' => 'Jones',
            'email' => 'carol@example.com',
            'mobile_number' => null,
            'home_number' => null,
        ],
    ];

    $job = new ProcessHousecallProJobCompletion($business, $jobData);
    $job->handle();

    $this->assertDatabaseHas('customers', [
        'business_id' => $business->id,
        'email' => 'carol@example.com',
    ]);

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $business->id,
        'source' => 'housecallpro',
        'status' => 'sent',
        'channel' => 'email',
    ]);

    Mail::assertQueued(ReviewRequestMail::class, fn ($mail) => $mail->hasTo('carol@example.com'));
});

test('ProcessHousecallProJobCompletion skips customer with recent review request within 90 days', function () {
    Mail::fake();

    $user = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'housecallpro',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $customer = Customer::factory()->create([
        'business_id' => $business->id,
        'email' => 'repeat@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'created_at' => now()->subDays(14),
    ]);

    $jobData = [
        'id' => 'hcp-job-888',
        'customer' => [
            'first_name' => 'Repeat',
            'last_name' => 'Customer',
            'email' => 'repeat@example.com',
            'mobile_number' => null,
            'home_number' => null,
        ],
    ];

    $job = new ProcessHousecallProJobCompletion($business, $jobData);
    $job->handle();

    // Should still be exactly 1 review request (the pre-existing one)
    expect(ReviewRequest::where('business_id', $business->id)->count())->toBe(1);
    Mail::assertNothingQueued();
});
