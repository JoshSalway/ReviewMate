<?php

use App\Jobs\ProcessJobberJobCompletion;
use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

// ─── Webhook ────────────────────────────────────────────────────────────────

test('jobber webhook queues job for JOB_UPDATED topic with a jobId', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'jobber',
        'access_token'      => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/jobber/{$business->uuid}", [
        'data' => [
            'webHookEvent' => [
                'topic' => 'JOB_UPDATED',
                'data'  => ['jobId' => 'job-abc-123'],
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);
    Queue::assertPushed(ProcessJobberJobCompletion::class, function ($job) use ($business) {
        return $job->business->id === $business->id && $job->jobId === 'job-abc-123';
    });
});

test('jobber webhook ignores non-JOB_UPDATED topics', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'jobber',
        'access_token'      => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/jobber/{$business->uuid}", [
        'data' => [
            'webHookEvent' => [
                'topic' => 'JOB_CREATED',
                'data'  => ['jobId' => 'job-abc-456'],
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessJobberJobCompletion::class);
});

test('jobber webhook returns 404 for unknown business uuid', function () {
    $response = $this->postJson('/webhooks/jobber/00000000-0000-0000-0000-000000000000', [
        'data' => [
            'webHookEvent' => [
                'topic' => 'JOB_UPDATED',
                'data'  => ['jobId' => 'job-xyz'],
            ],
        ],
    ]);

    $response->assertStatus(404);
});

test('jobber webhook returns ignored when auto_send disabled', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'jobber',
        'access_token'      => 'test-token',
        'auto_send_reviews' => false,
    ]);

    $response = $this->postJson("/webhooks/jobber/{$business->uuid}", [
        'data' => [
            'webHookEvent' => [
                'topic' => 'JOB_UPDATED',
                'data'  => ['jobId' => 'job-abc-123'],
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'auto_send_disabled']);
    Queue::assertNotPushed(ProcessJobberJobCompletion::class);
});

test('jobber webhook returns ignored when jobId is missing', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'jobber',
        'access_token'      => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/jobber/{$business->uuid}", [
        'data' => [
            'webHookEvent' => [
                'topic' => 'JOB_UPDATED',
                'data'  => [],
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessJobberJobCompletion::class);
});

// ─── Controller actions ──────────────────────────────────────────────────────

test('jobber disconnect removes integration and redirects', function () {
    $user     = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id'  => $business->id,
        'provider'     => 'jobber',
        'access_token' => 'test-token',
    ]);

    $this->actingAs($user)
        ->post('/integrations/jobber/disconnect')
        ->assertRedirect('/settings/integrations');

    expect($business->integration('jobber'))->toBeNull();
});

test('jobber toggleAutoSend flips auto_send_reviews', function () {
    $user        = User::factory()->create();
    $business    = Business::factory()->create(['user_id' => $user->id]);
    $integration = BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'jobber',
        'access_token'      => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $this->actingAs($user)->post('/integrations/jobber/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeFalse();

    $this->actingAs($user)->post('/integrations/jobber/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeTrue();
});

test('jobber connect redirects unauthenticated users to login', function () {
    $response = $this->get('/integrations/jobber/connect');
    $response->assertRedirect('/login');
});

// ─── Integrations page ───────────────────────────────────────────────────────

test('integrations page shows jobberConnected true when token present', function () {
    $user     = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id'  => $business->id,
        'provider'     => 'jobber',
        'access_token' => 'test-token',
    ]);

    $this->actingAs($user)
        ->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('jobberConnected', true)
        );
});

// ─── ProcessJobberJobCompletion job ──────────────────────────────────────────

test('ProcessJobberJobCompletion creates customer and queues email for COMPLETED job', function () {
    Mail::fake();

    $user     = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'jobber',
        'access_token'      => 'test-token',
        'refresh_token'     => 'test-refresh',
        'token_expires_at'  => now()->addHour(),
        'auto_send_reviews' => true,
    ]);

    Http::fake([
        'api.getjobber.com/api/graphql' => Http::response([
            'data' => [
                'job' => [
                    'jobStatus' => 'COMPLETED',
                    'client'    => [
                        'name'   => 'John Doe',
                        'email'  => 'john@example.com',
                        'phones' => [
                            ['number' => '+61400000001', 'primary' => true],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $job = new ProcessJobberJobCompletion($business, 'job-id-001');
    $job->handle();

    $this->assertDatabaseHas('customers', [
        'business_id' => $business->id,
        'email'       => 'john@example.com',
    ]);

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $business->id,
        'source'      => 'jobber',
        'status'      => 'sent',
    ]);

    Mail::assertQueued(ReviewRequestMail::class, fn ($mail) => $mail->hasTo('john@example.com'));
});

test('ProcessJobberJobCompletion skips non-COMPLETED jobs', function () {
    Mail::fake();

    $user     = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id'      => $business->id,
        'provider'         => 'jobber',
        'access_token'     => 'test-token',
        'refresh_token'    => 'test-refresh',
        'token_expires_at' => now()->addHour(),
    ]);

    Http::fake([
        'api.getjobber.com/api/graphql' => Http::response([
            'data' => [
                'job' => [
                    'jobStatus' => 'IN_PROGRESS',
                    'client'    => [
                        'name'   => 'Jane Doe',
                        'email'  => 'jane@example.com',
                        'phones' => [],
                    ],
                ],
            ],
        ], 200),
    ]);

    $job = new ProcessJobberJobCompletion($business, 'job-id-002');
    $job->handle();

    $this->assertDatabaseMissing('review_requests', [
        'business_id' => $business->id,
        'source'      => 'jobber',
    ]);

    Mail::assertNothingQueued();
});

test('ProcessJobberJobCompletion skips customer with recent review request within 90 days', function () {
    Mail::fake();

    $user     = User::factory()->create();
    $business = Business::factory()->onboarded()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id'      => $business->id,
        'provider'         => 'jobber',
        'access_token'     => 'test-token',
        'refresh_token'    => 'test-refresh',
        'token_expires_at' => now()->addHour(),
    ]);

    Http::fake([
        'api.getjobber.com/api/graphql' => Http::response([
            'data' => [
                'job' => [
                    'jobStatus' => 'COMPLETED',
                    'client'    => [
                        'name'   => 'Repeat Customer',
                        'email'  => 'repeat@example.com',
                        'phones' => [],
                    ],
                ],
            ],
        ], 200),
    ]);

    $customer = Customer::factory()->create([
        'business_id' => $business->id,
        'email'       => 'repeat@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'created_at'  => now()->subDays(30),
    ]);

    $job = new ProcessJobberJobCompletion($business, 'job-id-003');
    $job->handle();

    // Should still be exactly 1 review request (the pre-existing one)
    expect(ReviewRequest::where('business_id', $business->id)->count())->toBe(1);
    Mail::assertNothingQueued();
});
