<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// ── QuickSend email-only customer dedup tests ─────────────────────────────────
//
// NEXT_STEPS.md documented that QuickSendController previously used firstOrCreate
// with a composite key {email, phone}. This was fixed: the controller now looks up
// by email alone so an existing customer is found even if they have a phone number.
//
// These tests verify the CURRENT (fixed) behaviour.

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('quick send correctly finds unsubscribed customer who has a phone number via email-only lookup', function () {
    // Customer has both email AND phone and is unsubscribed.
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'unsub-with-phone@example.com',
        'phone' => '0412345678',
        'unsubscribed_at' => now()->subDay(),
    ]);

    $customerCountBefore = $this->business->customers()->count();

    // Quick-send with email only — should find the existing customer and block on unsubscribe
    $this->post('/quick-send', [
        'name' => 'Unsub With Phone',
        'email' => 'unsub-with-phone@example.com',
        'channel' => 'email',
    ])->assertSessionHas('error');

    // No duplicate customer should be created
    expect($this->business->customers()->count())->toBe($customerCountBefore);

    Mail::assertNothingQueued();
});

test('quick send correctly finds customer with 30-day guard who has a phone number via email-only lookup', function () {
    // Customer has both email AND phone with a recent review request.
    $existing = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'recent-with-phone@example.com',
        'phone' => '0498765432',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $existing->id,
        'created_at' => now()->subDays(5), // within 30-day guard
    ]);

    $requestCountBefore = ReviewRequest::where('business_id', $this->business->id)->count();

    // Quick-send with email only — should find the existing customer and block on 30-day guard
    $this->post('/quick-send', [
        'name' => 'Recent With Phone',
        'email' => 'recent-with-phone@example.com',
        'channel' => 'email',
    ])->assertSessionHas('error');

    // No new review request should be created (guard fired correctly)
    expect(ReviewRequest::where('business_id', $this->business->id)->count())->toBe($requestCountBefore);

    Mail::assertNothingQueued();
});

test('quick send correctly deduplicates when customer has no phone (null phone lookup also works)', function () {
    // When the existing customer has NO phone, email-only lookup still finds them.
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'no-phone@example.com',
        'phone' => null,
        'unsubscribed_at' => now()->subDay(),
    ]);

    $this->post('/quick-send', [
        'name' => 'No Phone Customer',
        'email' => 'no-phone@example.com',
        'channel' => 'email',
    ])->assertSessionHas('error'); // unsubscribed guard fires correctly

    Mail::assertNothingQueued();
});

test('quick send creates new customer when no matching email exists', function () {
    $countBefore = $this->business->customers()->count();

    $this->post('/quick-send', [
        'name' => 'Brand New Customer',
        'email' => 'brandnew@example.com',
        'channel' => 'email',
    ])->assertSessionHas('success');

    expect($this->business->customers()->count())->toBe($countBefore + 1);
});
