<?php

use App\Jobs\ProcessServiceM8JobCompletion;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('servicem8 webhook queues job for known business on job completion', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'servicem8',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/servicem8/{$business->uuid}", [
        'entry_point' => 'JobCompletion',
        'object_uuid' => 'job-uuid-123',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);
    Queue::assertPushed(ProcessServiceM8JobCompletion::class, function ($job) use ($business) {
        return $job->business->id === $business->id && $job->jobUuid === 'job-uuid-123';
    });
});

test('servicem8 webhook ignores non-completion events', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'servicem8',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/servicem8/{$business->uuid}", [
        'entry_point' => 'JobCreation',
        'object_uuid' => 'job-uuid-456',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessServiceM8JobCompletion::class);
});

test('servicem8 webhook returns 404 for unknown business uuid', function () {
    $response = $this->postJson('/webhooks/servicem8/00000000-0000-0000-0000-000000000000', [
        'entry_point' => 'JobCompletion',
        'object_uuid' => 'job-uuid',
    ]);

    $response->assertStatus(404);
});

test('servicem8 webhook skips queuing when auto send is disabled', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'servicem8',
        'access_token' => 'test-token',
        'auto_send_reviews' => false,
    ]);

    $response = $this->postJson("/webhooks/servicem8/{$business->uuid}", [
        'entry_point' => 'JobCompletion',
        'object_uuid' => 'job-uuid',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'auto_send_disabled']);
    Queue::assertNotPushed(ProcessServiceM8JobCompletion::class);
});

test('servicem8 webhook returns 400 when object_uuid is missing', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'servicem8',
        'access_token' => 'test-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/servicem8/{$business->uuid}", [
        'entry_point' => 'JobCompletion',
    ]);

    $response->assertStatus(400);
    $response->assertJson(['status' => 'no job uuid']);
    Queue::assertNotPushed(ProcessServiceM8JobCompletion::class);
});

test('servicem8 connect redirects unauthenticated users to login', function () {
    $response = $this->get('/integrations/servicem8/connect');
    $response->assertRedirect('/login');
});

test('servicem8 integrations settings page renders for authenticated user', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->has('servicem8Connected')
            ->has('servicem8AutoSend')
        );
});

test('servicem8 integrations page shows connected status when token is present', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'servicem8',
        'access_token' => 'test-token',
    ]);

    $this->actingAs($user)
        ->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('servicem8Connected', true)
        );
});
