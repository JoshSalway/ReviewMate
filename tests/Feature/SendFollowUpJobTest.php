<?php

use App\Jobs\SendFollowUpRequests;
use App\Mail\FollowUpMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
        'follow_up_enabled' => true,
        'follow_up_days' => 3,
        'follow_up_channel' => 'same',
    ]);
});

test('follow-up job sends mail after configured follow_up_days', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(4),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertQueued(FollowUpMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));
});

test('follow-up job skips requests not yet past follow_up_days', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(2), // Only 2 days ago, follow_up_days = 3
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

test('follow-up job respects custom follow_up_days per business', function () {
    $this->business->update(['follow_up_days' => 7]);

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(5), // 5 days, but follow_up_days = 7 — not ready
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

test('follow-up job sends after 7 days when configured', function () {
    $this->business->update(['follow_up_days' => 7]);

    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(8),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertQueued(FollowUpMail::class);
});

test('follow-up job skips requests with follow_up_enabled false', function () {
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
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

test('follow-up job skips already followed-up requests', function () {
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
        'followed_up_at' => now()->subDays(2),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

test('follow-up job skips reviewed requests', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->reviewed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'sent_at' => now()->subDays(5),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

test('follow-up job sets status to followed_up', function () {
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
    ]);

    (new SendFollowUpRequests)->handle();

    expect($request->fresh()->status)->toBe('followed_up');
    expect($request->fresh()->followed_up_at)->not->toBeNull();
});

test('follow-up job skips unsubscribed customers', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
        'unsubscribed_at' => now()->subDay(),
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(4),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});
