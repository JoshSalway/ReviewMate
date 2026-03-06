<?php

use App\Jobs\ProcessXeroInvoicePaid;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\XeroService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

// ---------------------------------------------------------------------------
// Webhook endpoint tests
// ---------------------------------------------------------------------------

test('xero webhook queues job for paid invoice UPDATE event', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'test-token',
        'meta' => ['tenant_id' => 'test-tenant'],
        'auto_send_reviews' => true,
    ]);

    $webhookKey = 'test-webhook-key';
    config(['services.xero.webhook_key' => $webhookKey]);

    $payload = json_encode([
        'events' => [[
            'eventType' => 'UPDATE',
            'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Invoices/abc-123',
        ]],
    ]);

    $signature = base64_encode(hash_hmac('sha256', $payload, $webhookKey, true));

    $response = $this->withHeaders(['x-xero-signature' => $signature])
        ->postJson("/webhooks/xero/{$business->uuid}", json_decode($payload, true));

    $response->assertStatus(200);
    Queue::assertPushed(ProcessXeroInvoicePaid::class, function ($job) use ($business) {
        return $job->business->is($business) && $job->invoiceId === 'abc-123';
    });
});

test('xero webhook rejects invalid signature', function () {
    $business = Business::factory()->create();
    config(['services.xero.webhook_key' => 'correct-key']);

    $response = $this->withHeaders(['x-xero-signature' => 'wrong-sig'])
        ->postJson("/webhooks/xero/{$business->uuid}", ['events' => []]);

    $response->assertStatus(401);
    $response->assertJson(['status' => 'invalid signature']);
});

test('xero webhook skips dispatching when auto send is disabled', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'test-token',
        'meta' => ['tenant_id' => 'test-tenant'],
        'auto_send_reviews' => false,
    ]);

    $webhookKey = 'test-key-2';
    config(['services.xero.webhook_key' => $webhookKey]);

    $payload = json_encode([
        'events' => [[
            'eventType' => 'UPDATE',
            'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Invoices/xyz-456',
        ]],
    ]);
    $signature = base64_encode(hash_hmac('sha256', $payload, $webhookKey, true));

    $this->withHeaders(['x-xero-signature' => $signature])
        ->postJson("/webhooks/xero/{$business->uuid}", json_decode($payload, true))
        ->assertStatus(200);

    Queue::assertNotPushed(ProcessXeroInvoicePaid::class);
});

test('xero webhook ignores non-UPDATE events', function () {
    Queue::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'test-token',
        'meta' => ['tenant_id' => 'test-tenant'],
        'auto_send_reviews' => true,
    ]);

    $webhookKey = 'test-key-3';
    config(['services.xero.webhook_key' => $webhookKey]);

    $payload = json_encode([
        'events' => [[
            'eventType' => 'CREATE',
            'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Invoices/new-123',
        ]],
    ]);
    $signature = base64_encode(hash_hmac('sha256', $payload, $webhookKey, true));

    $this->withHeaders(['x-xero-signature' => $signature])
        ->postJson("/webhooks/xero/{$business->uuid}", json_decode($payload, true))
        ->assertStatus(200);

    Queue::assertNotPushed(ProcessXeroInvoicePaid::class);
});

test('xero webhook returns 404 for unknown business uuid', function () {
    $webhookKey = 'key-404';
    config(['services.xero.webhook_key' => $webhookKey]);

    $payload = json_encode(['events' => []]);
    $signature = base64_encode(hash_hmac('sha256', $payload, $webhookKey, true));

    $this->withHeaders(['x-xero-signature' => $signature])
        ->postJson('/webhooks/xero/00000000-0000-0000-0000-000000000000', json_decode($payload, true))
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// ProcessXeroInvoicePaid job tests
// ---------------------------------------------------------------------------

test('ProcessXeroInvoicePaid creates customer and queues email when invoice is PAID', function () {
    Mail::fake();

    $business = Business::factory()->onboarded()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'test-token',
        'meta' => ['tenant_id' => 'test-tenant'],
    ]);

    Http::fake([
        '*/Invoices/*' => Http::response([
            'Invoices' => [[
                'InvoiceID' => 'inv-001',
                'Status' => 'PAID',
                'Contact' => ['ContactID' => 'contact-001'],
            ]],
        ]),
        '*/Contacts/*' => Http::response([
            'Contacts' => [[
                'ContactID' => 'contact-001',
                'Name' => 'Jane Smith',
                'EmailAddress' => 'jane@example.com',
                'Phones' => [],
            ]],
        ]),
    ]);

    $job = new \App\Jobs\ProcessXeroInvoicePaid($business, 'inv-001');
    $job->handle();

    $this->assertDatabaseHas('customers', [
        'business_id' => $business->id,
        'email' => 'jane@example.com',
        'name' => 'Jane Smith',
    ]);

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $business->id,
        'source' => 'xero',
        'status' => 'sent',
    ]);

    Mail::assertQueued(\App\Mail\ReviewRequestMail::class);
});

test('ProcessXeroInvoicePaid skips non-PAID invoices', function () {
    Mail::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'test-token',
        'meta' => ['tenant_id' => 'test-tenant'],
    ]);

    Http::fake([
        '*/Invoices/*' => Http::response([
            'Invoices' => [[
                'InvoiceID' => 'inv-002',
                'Status' => 'AUTHORISED',
                'Contact' => ['ContactID' => 'contact-002'],
            ]],
        ]),
    ]);

    $job = new \App\Jobs\ProcessXeroInvoicePaid($business, 'inv-002');
    $job->handle();

    $this->assertDatabaseCount('review_requests', 0);
    Mail::assertNothingQueued();
});

test('ProcessXeroInvoicePaid skips customer with recent review request', function () {
    Mail::fake();

    $business = Business::factory()->onboarded()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'test-token',
        'meta' => ['tenant_id' => 'test-tenant'],
    ]);

    $customer = Customer::factory()->create([
        'business_id' => $business->id,
        'email' => 'repeat@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'created_at' => now()->subDays(10),
    ]);

    Http::fake([
        '*/Invoices/*' => Http::response([
            'Invoices' => [[
                'InvoiceID' => 'inv-003',
                'Status' => 'PAID',
                'Contact' => ['ContactID' => 'contact-003'],
            ]],
        ]),
        '*/Contacts/*' => Http::response([
            'Contacts' => [[
                'ContactID' => 'contact-003',
                'Name' => 'Repeat Customer',
                'EmailAddress' => 'repeat@example.com',
                'Phones' => [],
            ]],
        ]),
    ]);

    $job = new \App\Jobs\ProcessXeroInvoicePaid($business, 'inv-003');
    $job->handle();

    $this->assertDatabaseCount('review_requests', 1);
    Mail::assertNothingQueued();
});

test('ProcessXeroInvoicePaid skips contact with no email and no phone', function () {
    Mail::fake();

    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'test-token',
        'meta' => ['tenant_id' => 'test-tenant'],
    ]);

    Http::fake([
        '*/Invoices/*' => Http::response([
            'Invoices' => [[
                'InvoiceID' => 'inv-004',
                'Status' => 'PAID',
                'Contact' => ['ContactID' => 'contact-004'],
            ]],
        ]),
        '*/Contacts/*' => Http::response([
            'Contacts' => [[
                'ContactID' => 'contact-004',
                'Name' => 'No Contact Info',
                'EmailAddress' => null,
                'Phones' => [],
            ]],
        ]),
    ]);

    $job = new \App\Jobs\ProcessXeroInvoicePaid($business, 'inv-004');
    $job->handle();

    $this->assertDatabaseCount('review_requests', 0);
    Mail::assertNothingQueued();
});

// ---------------------------------------------------------------------------
// XeroService unit tests
// ---------------------------------------------------------------------------

test('XeroService isConnected returns false when tokens are missing', function () {
    $business = Business::factory()->create();

    $service = new XeroService($business);

    expect($service->isConnected())->toBeFalse();
});

test('XeroService isConnected returns true when tokens are present', function () {
    $business = Business::factory()->create();
    BusinessIntegration::create([
        'business_id' => $business->id,
        'provider' => 'xero',
        'access_token' => 'some-token',
        'meta' => ['tenant_id' => 'some-tenant-id'],
    ]);

    $service = new XeroService($business);

    expect($service->isConnected())->toBeTrue();
});
