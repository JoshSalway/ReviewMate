<?php

use App\Mail\ReviewRequestMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access quick send page', function () {
    auth()->logout();
    $this->get('/quick-send')->assertRedirect('/login');
});

test('users can view the quick send page', function () {
    $this->get('/quick-send')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('quick-send'));
});

test('users can send a quick review request', function () {
    $this->post('/quick-send', [
        'name' => 'Sarah Johnson',
        'email' => 'sarah@example.com',
        'channel' => 'email',
    ])->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'business_id' => $this->business->id,
        'name' => 'Sarah Johnson',
        'email' => 'sarah@example.com',
    ]);

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $this->business->id,
        'status' => 'sent',
        'channel' => 'email',
    ]);
});

test('quick send creates a new customer if they do not exist', function () {
    expect($this->business->customers()->count())->toBe(0);

    $this->post('/quick-send', [
        'name' => 'New Customer',
        'email' => 'new@example.com',
        'channel' => 'email',
    ]);

    expect($this->business->customers()->count())->toBe(1);
});

test('quick send channel is required', function () {
    $this->post('/quick-send', [
        'name' => 'Test',
        'email' => 'test@example.com',
    ])->assertSessionHasErrors('channel');
});

test('quick send blocks unsubscribed customers when matched by email only (no phone)', function () {
    // Note: firstOrCreate uses email + phone as composite key. To reliably match on email only,
    // the customer must have phone = null so the lookup `{email: X, phone: null}` finds them.
    // This documents a known limitation: customers with a phone number are NOT deduped when
    // quick-send is given email only (see NEXT_STEPS.md).
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'unsub@example.com',
        'phone' => null,
        'unsubscribed_at' => now()->subDay(),
    ]);

    $this->post('/quick-send', [
        'name' => 'Unsub Person',
        'email' => 'unsub@example.com',
        'channel' => 'email',
    ])->assertSessionHas('error');

    Mail::assertNothingQueued();
});

test('quick send blocks customers with recent request when matched by email only (no phone)', function () {
    // Same composite-key dedup caveat as above test.
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'recent@example.com',
        'phone' => null,
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'created_at' => now()->subDays(5),
    ]);

    $this->post('/quick-send', [
        'name' => 'Recent Person',
        'email' => 'recent@example.com',
        'channel' => 'email',
    ])->assertSessionHas('error');

    Mail::assertNothingQueued();
});

test('quick send page shows recently sent requests capped at 5', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    ReviewRequest::factory()->count(8)->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
    ]);

    $this->get('/quick-send')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentlySent', 5)
        );
});

test('quick send queues email when channel is email', function () {
    $this->post('/quick-send', [
        'name' => 'Email Person',
        'email' => 'emailperson@example.com',
        'channel' => 'email',
    ])->assertSessionHas('success');

    Mail::assertQueued(ReviewRequestMail::class);
});

test('quick send page includes channel field in recently_sent items', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'channel' => 'email',
    ]);

    $this->get('/quick-send')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentlySent', 1)
            ->where('recentlySent.0.channel', 'email')
        );
});

test('quick send page passes prefill name and email from query params as Inertia props', function () {
    $this->get('/quick-send?name=John&email=john@example.com')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('quick-send')
            ->where('prefill.name', 'John')
            ->where('prefill.email', 'john@example.com')
        );
});

test('quick send page returns empty prefill when no query params provided', function () {
    $this->get('/quick-send')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('prefill.name', '')
            ->where('prefill.email', '')
        );
});

test('quick send enforces free plan monthly limit', function () {
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => Customer::factory()->create(['business_id' => $this->business->id])->id,
        'created_at' => now(),
    ]);

    $this->post('/quick-send', [
        'name' => 'New Person',
        'email' => 'newperson@example.com',
        'channel' => 'email',
    ])->assertSessionHas('error');

    Mail::assertNothingQueued();
});

test('quick send page passes at_request_limit as false when under free plan limit', function () {
    ReviewRequest::factory()->count(5)->create([
        'business_id' => $this->business->id,
        'customer_id' => Customer::factory()->create(['business_id' => $this->business->id])->id,
        'created_at' => now(),
    ]);

    $this->get('/quick-send')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('at_request_limit', false)
        );
});

test('quick send page passes at_request_limit as true when free plan limit is reached', function () {
    ReviewRequest::factory()->count(10)->create([
        'business_id' => $this->business->id,
        'customer_id' => Customer::factory()->create(['business_id' => $this->business->id])->id,
        'created_at' => now(),
    ]);

    $this->get('/quick-send')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('at_request_limit', true)
        );
});
