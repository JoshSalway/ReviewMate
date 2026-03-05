<?php

use App\Jobs\ProcessTimelyAppointmentCompleted;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user     = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

// --- Webhook ---

test('timely webhook queues job for appointment.completed event', function () {
    Queue::fake();

    BusinessIntegration::create([
        'business_id'       => $this->business->id,
        'provider'          => 'timely',
        'access_token'      => 'some-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/timely/{$this->business->uuid}", [
        'event' => 'appointment.completed',
        'data'  => ['client_id' => 42, 'status' => 'completed'],
    ]);

    $response->assertOk()->assertJson(['status' => 'queued']);
    Queue::assertPushed(ProcessTimelyAppointmentCompleted::class);
});

test('timely webhook ignores non-completion events', function () {
    Queue::fake();

    BusinessIntegration::create([
        'business_id'       => $this->business->id,
        'provider'          => 'timely',
        'access_token'      => 'some-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/timely/{$this->business->uuid}", [
        'event' => 'appointment.created',
        'data'  => ['client_id' => 42],
    ]);

    $response->assertOk()->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessTimelyAppointmentCompleted::class);
});

test('timely webhook ignores appointment.updated that is not completed status', function () {
    Queue::fake();

    BusinessIntegration::create([
        'business_id'       => $this->business->id,
        'provider'          => 'timely',
        'access_token'      => 'some-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/timely/{$this->business->uuid}", [
        'event' => 'appointment.updated',
        'data'  => ['client_id' => 42, 'status' => 'cancelled'],
    ]);

    $response->assertOk()->assertJson(['status' => 'ignored']);
    Queue::assertNotPushed(ProcessTimelyAppointmentCompleted::class);
});

test('timely webhook processes appointment.updated with completed status', function () {
    Queue::fake();

    BusinessIntegration::create([
        'business_id'       => $this->business->id,
        'provider'          => 'timely',
        'access_token'      => 'some-token',
        'auto_send_reviews' => true,
    ]);

    $response = $this->postJson("/webhooks/timely/{$this->business->uuid}", [
        'event' => 'appointment.updated',
        'data'  => ['client_id' => 42, 'status' => 'completed'],
    ]);

    $response->assertOk()->assertJson(['status' => 'queued']);
    Queue::assertPushed(ProcessTimelyAppointmentCompleted::class);
});

test('timely webhook skips when auto_send is disabled', function () {
    Queue::fake();

    BusinessIntegration::create([
        'business_id'       => $this->business->id,
        'provider'          => 'timely',
        'access_token'      => 'some-token',
        'auto_send_reviews' => false,
    ]);

    $response = $this->postJson("/webhooks/timely/{$this->business->uuid}", [
        'event' => 'appointment.completed',
        'data'  => ['client_id' => 42],
    ]);

    $response->assertOk()->assertJson(['status' => 'auto_send_disabled']);
    Queue::assertNotPushed(ProcessTimelyAppointmentCompleted::class);
});

// --- ProcessTimelyAppointmentCompleted job ---

test('job creates review request from embedded client data', function () {
    Mail::fake();

    BusinessIntegration::create([
        'business_id'  => $this->business->id,
        'provider'     => 'timely',
        'access_token' => 'some-token',
        'meta'         => ['account_id' => '999'],
    ]);

    $job = new ProcessTimelyAppointmentCompleted($this->business, [
        'client' => [
            'first_name'  => 'Sara',
            'last_name'   => 'Jones',
            'email'       => 'sara@example.com',
            'mobile_phone' => null,
        ],
    ]);

    $job->handle();

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $this->business->id,
        'source'      => 'timely',
        'status'      => 'sent',
        'channel'     => 'email',
    ]);
});

test('job fetches client from api when not embedded in payload', function () {
    Mail::fake();

    Http::fake([
        'api.gettimely.com/999/clients/42' => Http::response([
            'first_name'  => 'Mark',
            'last_name'   => 'Taylor',
            'email'       => 'mark@example.com',
            'mobile_phone' => null,
        ], 200),
    ]);

    BusinessIntegration::create([
        'business_id'  => $this->business->id,
        'provider'     => 'timely',
        'access_token' => 'some-token',
        'meta'         => ['account_id' => '999'],
    ]);

    $job = new ProcessTimelyAppointmentCompleted($this->business, [
        'client_id' => 42,
    ]);

    $job->handle();

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $this->business->id,
        'source'      => 'timely',
        'status'      => 'sent',
    ]);
});

test('job skips client already sent a review within 90 days', function () {
    Mail::fake();

    BusinessIntegration::create([
        'business_id'  => $this->business->id,
        'provider'     => 'timely',
        'access_token' => 'some-token',
        'meta'         => ['account_id' => '999'],
    ]);

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email'       => 'repeat@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'created_at'  => now()->subDays(10), // within 90-day window
    ]);

    $job = new ProcessTimelyAppointmentCompleted($this->business, [
        'client' => [
            'first_name'  => 'Repeat',
            'last_name'   => 'Customer',
            'email'       => 'repeat@example.com',
            'mobile_phone' => null,
        ],
    ]);

    $job->handle();

    // No new review request should be created
    expect(ReviewRequest::where('business_id', $this->business->id)->count())->toBe(1);
    Mail::assertNothingQueued();
});

test('job skips when client has no contact details', function () {
    Mail::fake();

    BusinessIntegration::create([
        'business_id'  => $this->business->id,
        'provider'     => 'timely',
        'access_token' => 'some-token',
        'meta'         => ['account_id' => '999'],
    ]);

    $job = new ProcessTimelyAppointmentCompleted($this->business, [
        'client' => [
            'first_name'  => 'Ghost',
            'last_name'   => 'Client',
            'email'       => null,
            'mobile_phone' => null,
            'phone'        => null,
        ],
    ]);

    $job->handle();

    expect(ReviewRequest::where('business_id', $this->business->id)->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('job skips when no client_id and no account_id configured', function () {
    Mail::fake();

    BusinessIntegration::create([
        'business_id'  => $this->business->id,
        'provider'     => 'timely',
        'access_token' => 'some-token',
        'meta'         => ['account_id' => null],
    ]);

    $job = new ProcessTimelyAppointmentCompleted($this->business, [
        'client_id' => 42,
        // no embedded client, no account_id
    ]);

    $job->handle();

    expect(ReviewRequest::where('business_id', $this->business->id)->count())->toBe(0);
    Mail::assertNothingQueued();
});

// --- Timely connect/disconnect ---

test('timely disconnect clears all token fields', function () {
    BusinessIntegration::create([
        'business_id'      => $this->business->id,
        'provider'         => 'timely',
        'access_token'     => 'token',
        'refresh_token'    => 'refresh',
        'token_expires_at' => now()->addHour(),
        'meta'             => ['account_id' => '123'],
    ]);

    $response = $this->post('/integrations/timely/disconnect');
    $response->assertRedirect('/settings/integrations');

    expect($this->business->integration('timely'))->toBeNull();
});

test('timely toggle auto send flips the setting', function () {
    $integration = BusinessIntegration::create([
        'business_id'       => $this->business->id,
        'provider'          => 'timely',
        'access_token'      => 'token',
        'auto_send_reviews' => true,
    ]);

    $this->post('/integrations/timely/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeFalse();

    $this->post('/integrations/timely/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeTrue();
});

// --- Integrations page ---

test('integrations page includes timely props', function () {
    BusinessIntegration::create([
        'business_id'       => $this->business->id,
        'provider'          => 'timely',
        'access_token'      => 'some-token',
        'auto_send_reviews' => true,
    ]);

    $this->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('timelyConnected', true)
            ->where('timelyAutoSend', true)
        );
});
