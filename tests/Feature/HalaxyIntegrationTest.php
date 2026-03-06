<?php

use App\Jobs\PollHalaxyAppointments;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

// --- HalaxyController store ---

test('valid halaxy api key is stored', function () {
    Http::fake([
        'api.halaxy.com/v1/practitioners*' => Http::response(['practitioners' => [['id' => 1]]], 200),
    ]);

    $response = $this->post('/integrations/halaxy/connect', [
        'api_key' => 'halaxy-test-api-key-123',
    ]);

    $response->assertRedirect('/settings/integrations');

    $integration = $this->business->integration('halaxy');
    expect($integration->api_key)->toBe('halaxy-test-api-key-123');
});

test('invalid halaxy api key returns validation error', function () {
    Http::fake([
        'api.halaxy.com/v1/practitioners*' => Http::response([], 401),
    ]);

    $response = $this->post('/integrations/halaxy/connect', [
        'api_key' => 'bad-key-that-fails',
    ]);

    $response->assertSessionHasErrors('api_key');

    expect($this->business->integration('halaxy'))->toBeNull();
});

test('halaxy connection requires api_key field', function () {
    $response = $this->post('/integrations/halaxy/connect', []);
    $response->assertSessionHasErrors('api_key');
});

// --- HalaxyController disconnect ---

test('halaxy disconnect clears integration fields', function () {
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'halaxy',
        'api_key' => 'test-key',
        'last_polled_at' => now(),
    ]);

    $response = $this->post('/integrations/halaxy/disconnect');
    $response->assertRedirect('/settings/integrations');

    expect($this->business->integration('halaxy'))->toBeNull();
});

// --- HalaxyController toggleAutoSend ---

test('halaxy toggle auto send flips the setting', function () {
    $integration = BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'halaxy',
        'api_key' => 'test-key',
        'auto_send_reviews' => true,
    ]);

    $this->post('/integrations/halaxy/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeFalse();

    $this->post('/integrations/halaxy/toggle-auto-send');

    $integration->refresh();
    expect($integration->auto_send_reviews)->toBeTrue();
});

// --- PollHalaxyAppointments job ---

test('poll job skips business with no api key', function () {
    Mail::fake();

    $job = new PollHalaxyAppointments($this->business);
    $job->handle();

    Mail::assertNothingSent();
});

test('poll job skips business with auto send disabled', function () {
    Mail::fake();

    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'halaxy',
        'api_key' => 'some-key',
        'auto_send_reviews' => false,
    ]);

    $job = new PollHalaxyAppointments($this->business);
    $job->handle();

    Mail::assertNothingSent();
});

test('poll job skips patients already sent a review within 90 days', function () {
    Mail::fake();

    Http::fake([
        'api.halaxy.com/v1/appointments*' => Http::response([
            'data' => [
                [
                    'id' => 'appt_123',
                    'patient_id' => 'pat_456',
                    'status' => 'COMPLETED',
                    'start_time' => now()->subHours(3)->toIso8601String(),
                    'end_time' => now()->subHours(2)->toIso8601String(),
                ],
            ],
        ], 200),
        'api.halaxy.com/v1/patients/pat_456' => Http::response([
            'id' => 'pat_456',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'mobile' => null,
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
        'provider' => 'halaxy',
        'api_key' => 'some-key',
        'auto_send_reviews' => true,
    ]);

    $job = new PollHalaxyAppointments($this->business);
    $job->handle();

    // Should not have created a new review request
    expect(ReviewRequest::where('business_id', $this->business->id)->count())->toBe(1);
    Mail::assertNothingQueued();
});

test('poll job creates review request and updates last polled at', function () {
    Mail::fake();

    Http::fake([
        'api.halaxy.com/v1/appointments*' => Http::response([
            'data' => [
                [
                    'id' => 'appt_789',
                    'patient_id' => 'pat_999',
                    'status' => 'COMPLETED',
                    'start_time' => now()->subHours(3)->toIso8601String(),
                    'end_time' => now()->subHours(2)->toIso8601String(),
                ],
            ],
        ], 200),
        'api.halaxy.com/v1/patients/pat_999' => Http::response([
            'id' => 'pat_999',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'mobile' => null,
        ], 200),
    ]);

    $integration = BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'halaxy',
        'api_key' => 'some-key',
        'auto_send_reviews' => true,
        'last_polled_at' => now()->subDay(),
    ]);

    $job = new PollHalaxyAppointments($this->business);
    $job->handle();

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $this->business->id,
        'source' => 'halaxy',
        'status' => 'sent',
        'channel' => 'email',
    ]);

    $integration->refresh();
    expect($integration->last_polled_at)->not->toBeNull();
});

// --- Integrations page ---

test('integrations page includes halaxy props', function () {
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'halaxy',
        'api_key' => 'test-key',
        'auto_send_reviews' => false,
    ]);

    $this->get('/settings/integrations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/integrations')
            ->where('halaxyConnected', true)
            ->where('halaxyAutoSend', false)
        );
});
