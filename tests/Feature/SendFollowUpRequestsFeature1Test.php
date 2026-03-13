<?php

use App\Jobs\SendFollowUpRequests;
use App\Mail\FollowUpMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Feature 1: SendFollowUpRequests job correctness tests.
 *
 * Verifies the four key eligibility rules:
 *   1. Request past follow_up_days + follow_up_enabled + no review → sends, sets followed_up_at
 *   2. Request where a review exists for that customer → NOT followed up
 *   3. Business has follow_up_enabled = false → NOT followed up
 *   4. Request already has followed_up_at set → NOT followed up again
 */
beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
        'follow_up_enabled' => true,
        'follow_up_days' => 3,
        'follow_up_channel' => 'email',
    ]);
});

test('sends follow-up and sets followed_up_at when request is past follow_up_days with no review', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(4),
        'followed_up_at' => null,
        'reviewed_at' => null,
    ]);

    (new SendFollowUpRequests($this->business))->handle();

    Mail::assertQueued(FollowUpMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));

    expect($request->fresh()->followed_up_at)->not->toBeNull();
    expect($request->fresh()->status)->toBe('followed_up');
});

test('does not follow up when customer has already left a review (reviewed_at set)', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    // A review exists, linked to this request
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'reviewed',
        'channel' => 'email',
        'sent_at' => now()->subDays(5),
        'reviewed_at' => now()->subDay(),
        'followed_up_at' => null,
    ]);

    Review::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'review_request_id' => $request->id,
        'rating' => 5,
    ]);

    (new SendFollowUpRequests($this->business))->handle();

    Mail::assertNothingQueued();
    expect($request->fresh()->followed_up_at)->toBeNull();
});

test('does not follow up when follow_up_enabled is false on the business', function () {
    $this->business->update(['follow_up_enabled' => false]);

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(5),
        'followed_up_at' => null,
        'reviewed_at' => null,
    ]);

    (new SendFollowUpRequests($this->business))->handle();

    Mail::assertNothingQueued();
});

test('does not follow up again when followed_up_at is already set', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'followed_up',
        'channel' => 'email',
        'sent_at' => now()->subDays(5),
        'followed_up_at' => now()->subDays(2),
        'reviewed_at' => null,
    ]);

    (new SendFollowUpRequests($this->business))->handle();

    Mail::assertNothingQueued();
    // followed_up_at remains the original value
    expect($request->fresh()->followed_up_at->toDateString())
        ->toBe(now()->subDays(2)->toDateString());
});
