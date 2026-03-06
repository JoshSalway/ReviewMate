<?php

use App\Jobs\PollClinikoAppointments;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Services\ClinikoService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

// --- ClinikoService::detectShard ---

test('detectShard extracts shard from api key', function () {
    expect(ClinikoService::detectShard('some-api-key==au1'))->toBe('au1');
    expect(ClinikoService::detectShard('some-api-key==au2'))->toBe('au2');
    expect(ClinikoService::detectShard('some-api-key==uk1'))->toBe('uk1');
    expect(ClinikoService::detectShard('some-api-key==sg1'))->toBe('sg1');
});

test('detectShard defaults to au1 when no shard in key', function () {
    expect(ClinikoService::detectShard('some-api-key-without-shard'))->toBe('au1');
});

// --- ClinikoController store ---

test('valid cliniko api key is stored and shard detected', function () {
    Http::fake([
        'api.au1.cliniko.com/v1/practitioners*' => Http::response(['practitioners' => []], 200),
    ]);

    $response = $this->post('/integrations/cliniko/connect', [
        'api_key' => 'test-api-key==au1',
    ]);

    $response->assertRedirect('/settings/integrations');

    $integration = $this->business->integration('cliniko');
    expect($integration->api_key)->toBe('test-api-key==au1');
    expect($integration->getMeta('shard'))->toBe('au1');
});

test('invalid cliniko api key returns validation error', function () {
    Http::fake([
        'api.au1.cliniko.com/v1/practitioners*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $response = $this->post('/integrations/cliniko/connect', [
        'api_key' => 'bad-key==au1',
    ]);

    $response->assertSessionHasErrors('api_key');

    expect($this->business->integration('cliniko'))->toBeNull();
});

test('cliniko connection requires api_key field', function () {
    $response = $this->post('/integrations/cliniko/connect', []);
    $response->assertSessionHasErrors('api_key');
});

test('cliniko disconnect clears integration fields', function () {
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'cliniko',
        'api_key' => 'test-key',
        'meta' => ['shard' => 'au1'],
        'last_polled_at' => now(),
    ]);

    $response = $this->post('/integrations/cliniko/disconnect');
    $response->assertRedirect('/settings/integrations');

    expect($this->business->integration('cliniko'))->toBeNull();
});

test('cliniko toggle auto send flips the setting', function () {
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'cliniko',
        'api_key' => 'test-key',
        'auto_send_reviews' => true,
    ]);

    $this->post('/integrations/cliniko/toggle-auto-send');

    $integration = $this->business->integration('cliniko');
    expect($integration->auto_send_reviews)->toBeFalse();

    $this->post('/integrations/cliniko/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeTrue();
});

// --- PollClinikoAppointments job ---

test('poll job skips business with no api key', function () {
    Queue::fake();
    Mail::fake();

    $job = new PollClinikoAppointments($this->business);
    $job->handle();

    Mail::assertNothingSent();
});

test('poll job skips business with auto send disabled', function () {
    Queue::fake();
    Mail::fake();

    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'cliniko',
        'api_key' => 'some-key==au1',
        'auto_send_reviews' => false,
    ]);

    $job = new PollClinikoAppointments($this->business);
    $job->handle();

    Mail::assertNothingSent();
});

test('poll job skips patients already sent a review within 90 days', function () {
    Mail::fake();

    $past = now()->subDays(7);

    Http::fake([
        'api.au1.cliniko.com/v1/appointments*' => Http::response([
            'appointments' => [
                [
                    'appointment_start' => $past->toIso8601String(),
                    'appointment_end' => $past->addHour()->toIso8601String(),
                    'cancelled' => false,
                    'patient' => [
                        'links' => ['self' => 'https://api.au1.cliniko.com/v1/patients/123'],
                    ],
                ],
            ],
        ], 200),
        'api.au1.cliniko.com/v1/patients/123' => Http::response([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'patient_phone_numbers' => [],
        ], 200),
    ]);

    // Pre-existing customer with recent request
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'jane@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'created_at' => now()->subDays(30), // within 90-day window
    ]);

    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'cliniko',
        'api_key' => 'some-key==au1',
        'meta' => ['shard' => 'au1'],
        'auto_send_reviews' => true,
    ]);

    $job = new PollClinikoAppointments($this->business);
    $job->handle();

    // Should not have created a new review request
    expect(ReviewRequest::where('business_id', $this->business->id)->count())->toBe(1);
    Mail::assertNothingQueued();
});

test('poll job creates review request and updates last polled at', function () {
    Mail::fake();

    $past = now()->subHours(2);

    Http::fake([
        'api.au1.cliniko.com/v1/appointments*' => Http::response([
            'appointments' => [
                [
                    'appointment_start' => $past->toIso8601String(),
                    'appointment_end' => $past->addHour()->toIso8601String(),
                    'cancelled' => false,
                    'patient' => [
                        'links' => ['self' => 'https://api.au1.cliniko.com/v1/patients/456'],
                    ],
                ],
            ],
        ], 200),
        'api.au1.cliniko.com/v1/patients/456' => Http::response([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'patient_phone_numbers' => [],
        ], 200),
    ]);

    $integration = BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'cliniko',
        'api_key' => 'some-key==au1',
        'meta' => ['shard' => 'au1'],
        'auto_send_reviews' => true,
        'last_polled_at' => now()->subDay(),
    ]);

    $job = new PollClinikoAppointments($this->business);
    $job->handle();

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $this->business->id,
        'source' => 'cliniko',
        'status' => 'sent',
        'channel' => 'email',
    ]);

    $integration->refresh();
    expect($integration->last_polled_at)->not->toBeNull();
});

// --- Integrations page shows all statuses ---

test('integrations page includes cliniko props', function () {
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'cliniko',
        'api_key' => 'test-key',
        'auto_send_reviews' => false,
    ]);

    $this->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('clinikoConnected', true)
            ->where('clinikoAutoSend', false)
        );
});
