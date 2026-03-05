<?php

use App\Jobs\ProcessSimproJobComplete;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

// --- Webhook tests ---

test('simpro webhook queues job when status is Complete', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'simpro',
        'access_token'      => 'test-token',
        'meta'              => ['company_url' => 'mycompany.simprocloud.com'],
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/simpro/{$business->uuid}", [
        'event' => 'job.status.changed',
        'data'  => [
            'jobId'      => 12345,
            'status'     => 'Complete',
            'customerId' => 67890,
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);
    Queue::assertPushed(ProcessSimproJobComplete::class, function ($job) use ($business) {
        return $job->business->id === $business->id && $job->jobId === 12345;
    });
});

test('simpro webhook ignores non-Complete statuses', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'simpro',
        'access_token'      => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/simpro/{$business->uuid}", [
        'event' => 'job.status.changed',
        'data'  => [
            'jobId'  => 12345,
            'status' => 'In Progress',
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessSimproJobComplete::class);
});

test('simpro webhook ignores unknown events', function () {
    Queue::fake();

    $business = Business::factory()->create();

    $response = $this->postJson("/webhooks/simpro/{$business->uuid}", [
        'event' => 'job.created',
        'data'  => ['jobId' => 1],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessSimproJobComplete::class);
});

test('simpro webhook returns 404 for unknown business uuid', function () {
    $response = $this->postJson('/webhooks/simpro/00000000-0000-0000-0000-000000000000', [
        'event' => 'job.status.changed',
        'data'  => ['jobId' => 1, 'status' => 'Complete'],
    ]);

    $response->assertStatus(404);
});

test('simpro webhook skips queuing when auto send is disabled', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'simpro',
        'access_token'      => 'test-token',
        'auto_send_reviews' => false,
    ]);

    $response = $this->postJson("/webhooks/simpro/{$business->uuid}", [
        'event' => 'job.status.changed',
        'data'  => [
            'jobId'  => 12345,
            'status' => 'Complete',
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'auto_send_disabled']);
    Queue::assertNotPushed(ProcessSimproJobComplete::class);
});

test('simpro webhook returns 400 when job id is missing', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'simpro',
        'access_token'      => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/simpro/{$business->uuid}", [
        'event' => 'job.status.changed',
        'data'  => ['status' => 'Complete'],
    ]);

    $response->assertStatus(400);
    $response->assertJson(['status' => 'no job id']);
    Queue::assertNotPushed(ProcessSimproJobComplete::class);
});

// --- Auth redirect ---

test('simpro connect redirects unauthenticated users to login', function () {
    $response = $this->post('/integrations/simpro/connect', [
        'company_url' => 'mycompany.simprocloud.com',
    ]);
    $response->assertRedirect('/login');
});

// --- Integrations page ---

test('integrations page includes simpro props', function () {
    $user     = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id'       => $business->id,
        'provider'          => 'simpro',
        'access_token'      => 'test-token',
        'meta'              => ['company_url' => 'mycompany.simprocloud.com'],
        'auto_send_reviews' => false,
    ]);

    $this->actingAs($user)
        ->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('simproConnected', true)
            ->where('simproAutoSend', false)
        );
});

test('integrations page shows simpro as disconnected when no token', function () {
    $user     = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('simproConnected', false)
        );
});
