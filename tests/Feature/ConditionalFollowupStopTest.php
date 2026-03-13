<?php

use App\Jobs\SendFollowUpRequests;
use App\Jobs\SyncGoogleReviews;
use App\Mail\FollowUpMail;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Support\Facades\Mail;

/**
 * Feature 2: Conditional Follow-up Stop
 *
 * When SyncGoogleReviews matches a new review to a customer's review_request,
 * the follow-up for that request should be cancelled.
 *
 * Also ensures SendFollowUpRequests never follows up when a review exists
 * for the request (via whereDoesntHave('reviews')).
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
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'google',
        'access_token' => 'fake-token',
        'meta' => ['location_id' => 'accounts/123/locations/456'],
    ]);
});

test('SendFollowUpRequests skips requests that have an associated review', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'customer@example.com',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
        'sent_at' => now()->subDays(5),
        'followed_up_at' => null,
        'reviewed_at' => null,
    ]);

    // A review exists linked to this request (customer already reviewed)
    Review::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'review_request_id' => $request->id,
        'rating' => 4,
    ]);

    (new SendFollowUpRequests($this->business))->handle();

    Mail::assertNothingQueued();
});

test('SyncGoogleReviews sets followed_up_at on matched request to cancel pending follow-up', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
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

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-jane',
            'name' => 'accounts/123/locations/456/reviews/review-jane',
            'starRating' => 'FIVE',
            'comment' => 'Great service!',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Jane Smith'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    $fresh = $request->fresh();
    expect($fresh->status)->toBe('reviewed');
    expect($fresh->followed_up_at)->not->toBeNull();
});

test('SyncGoogleReviews does not overwrite followed_up_at if already set', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Bob Jones',
        'email' => 'bob@example.com',
    ]);

    $originalFollowUpAt = now()->subDays(2);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'followed_up',
        'channel' => 'email',
        'sent_at' => now()->subDays(6),
        'followed_up_at' => $originalFollowUpAt,
        'reviewed_at' => null,
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-bob',
            'name' => 'accounts/123/locations/456/reviews/review-bob',
            'starRating' => 'FOUR',
            'comment' => 'Nice!',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Bob Jones'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    $fresh = $request->fresh();
    expect($fresh->status)->toBe('reviewed');
    // followed_up_at should remain the original value
    expect($fresh->followed_up_at->toDateString())->toBe($originalFollowUpAt->toDateString());
});
