<?php

use App\Mail\FollowUpMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
});

test('follow-up command sends mail to customers with old unanswered requests', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(4),
    ]);

    $this->artisan('reviewmate:send-followups')->assertSuccessful();

    Mail::assertQueued(FollowUpMail::class, fn ($mail) => $mail->hasTo('customer@example.com')
    );
});

test('follow-up command skips recently sent requests', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(1), // Only 1 day ago - not yet eligible
    ]);

    $this->artisan('reviewmate:send-followups')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('follow-up command skips customers who already reviewed', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->reviewed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'sent_at' => now()->subDays(4),
    ]);

    $this->artisan('reviewmate:send-followups')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('follow-up command skips customers with no email', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => null,
        'phone' => '0400000000',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(4),
    ]);

    $this->artisan('reviewmate:send-followups')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('follow-up command marks request as no_response after sending', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(4),
    ]);

    $this->artisan('reviewmate:send-followups');

    expect($request->fresh()->status)->toBe('no_response');
});
