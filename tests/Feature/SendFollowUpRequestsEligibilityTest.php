<?php

use App\Jobs\SendFollowUpRequests;
use App\Mail\FollowUpMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Focused eligibility tests for the SendFollowUpRequests job.
 *
 * The job should only target requests that are:
 *   - status: 'sent' or 'opened'
 *   - sent_at past the configured follow_up_days threshold
 *   - followed_up_at is null
 *   - reviewed_at is null
 *
 * Anything outside these criteria must be skipped.
 */
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

test('job only targets requests with status sent or opened', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    // Eligible: status = 'sent'
    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(4),
    ]);

    // Eligible: status = 'opened'
    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'opened',
        'channel' => 'email',
        'sent_at' => now()->subDays(60),
        'followed_up_at' => null,
        'reviewed_at' => null,
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertQueued(FollowUpMail::class, 2);
});

test('job does not target requests with status followed_up or reviewed', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'followed_up',
        'channel' => 'email',
        'sent_at' => now()->subDays(10),
        'followed_up_at' => now()->subDays(7),
    ]);

    ReviewRequest::factory()->reviewed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'sent_at' => now()->subDays(10),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

test('job does not target requests where followed_up_at is already set', function () {
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

test('job does not target requests not yet past the follow_up_days threshold', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    // Sent only 1 day ago — threshold is 3 days
    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(1),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

test('job is registered and scheduled in console routes', function () {
    // Verify the schedule is wired up by checking the console route file references the job
    $consoleRoutes = file_get_contents(base_path('routes/console.php'));

    expect($consoleRoutes)->toContain('SendFollowUpRequests')
        ->toContain('follow-up-requests');
});
