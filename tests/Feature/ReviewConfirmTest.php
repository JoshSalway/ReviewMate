<?php

use App\Jobs\SendFollowUpRequests;
use App\Jobs\SyncGoogleReviews;
use App\Mail\PrivateFeedbackMail;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
    ]);
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'google',
        'access_token' => 'fake-token',
        'meta' => ['location_id' => 'accounts/123/locations/456'],
    ]);
    $this->customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

it('marks review request as self_confirmed when token is valid', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
    ]);

    $this->get("/reviewed/{$request->tracking_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reviews/confirmed')
            ->where('customerName', 'Jane Doe')
            ->where('businessName', $this->business->name)
        );

    expect($request->fresh()->status)->toBe('self_confirmed');
    expect($request->fresh()->reviewed_at)->not->toBeNull();
});

it('is idempotent — returns thank you page if already reviewed', function () {
    $request = ReviewRequest::factory()->reviewed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->get("/reviewed/{$request->tracking_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reviews/confirmed'));

    // Status should remain 'reviewed', not downgraded
    expect($request->fresh()->status)->toBe('reviewed');
});

it('returns 404 for invalid token', function () {
    $this->get('/reviewed/nonexistent-invalid-token-xyz')->assertNotFound();
});

it('unsubscribe page shows review CTA when there is a pending request', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
    ]);

    $this->get("/unsubscribe/{$this->customer->unsubscribe_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('unsubscribed')
            ->where('businessName', $this->business->name)
            ->has('confirmUrl')
        );
});

it('clicking yes on unsubscribe marks self_confirmed', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
    ]);

    // First visit the unsubscribe page (which unsubscribes them)
    $this->get("/unsubscribe/{$this->customer->unsubscribe_token}")->assertOk();

    // Then they click the confirm URL returned to them
    $this->get("/reviewed/{$request->tracking_token}")->assertOk();

    expect($request->fresh()->status)->toBe('self_confirmed');
    expect($this->customer->fresh()->unsubscribed_at)->not->toBeNull();
});

it('SyncGoogleReviews upgrades self_confirmed to reviewed when google review found', function () {
    $request = ReviewRequest::factory()->selfConfirmed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'sent_at' => now()->subDays(3),
        'reviewed_at' => now()->subDays(2), // self-confirmed 2 days ago
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-match',
            'name' => 'accounts/123/locations/456/reviews/review-match',
            'starRating' => 'FIVE',
            'comment' => 'Great service!',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Jane Doe'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect($request->fresh()->status)->toBe('reviewed');
});

it('SyncGoogleReviews marks unverified_claim after 7 days with no google review', function () {
    $request = ReviewRequest::factory()->selfConfirmed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'sent_at' => now()->subDays(12),
        'reviewed_at' => now()->subDays(8), // self-confirmed 8 days ago — past 7-day window
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    // No matching review returned
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-other',
            'name' => 'accounts/123/locations/456/reviews/review-other',
            'starRating' => 'FOUR',
            'comment' => 'Ok.',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Completely Different Person'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect($request->fresh()->status)->toBe('unverified_claim');
});

it('SendFollowUpRequests skips self_confirmed requests', function () {
    $this->business->update([
        'follow_up_enabled' => true,
        'follow_up_days' => 3,
        'follow_up_channel' => 'same',
    ]);

    ReviewRequest::factory()->selfConfirmed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'channel' => 'email',
        'sent_at' => now()->subDays(5),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});

it('track renders the landing page instead of redirecting', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'sent',
    ]);

    $this->get("/r/{$request->tracking_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reviews/landing')
            ->where('token', $request->tracking_token)
            ->has('businessName')
            ->has('googleReviewUrl')
        );

    expect($request->fresh()->status)->toBe('opened');
});

it('submitFeedback saves private rating and feedback and notifies owner', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'opened',
    ]);

    $this->post("/r/{$request->tracking_token}/feedback", [
        'rating' => 2,
        'feedback' => 'The technician was late and left a mess.',
    ])->assertRedirect("/reviewed/{$request->tracking_token}");

    expect($request->fresh()->private_rating)->toBe(2);
    expect($request->fresh()->private_feedback)->toBe('The technician was late and left a mess.');
    expect($request->fresh()->status)->toBe('feedback_received');
    expect($request->fresh()->feedback_received_at)->not->toBeNull();

    Mail::assertQueued(PrivateFeedbackMail::class, fn ($mail) =>
        $mail->hasTo($this->user->email)
    );
});

it('submitFeedback for happy rating records it and still notifies owner', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'opened',
    ]);

    $this->post("/r/{$request->tracking_token}/feedback", [
        'rating' => 5,
        'feedback' => null,
    ])->assertRedirect("/reviewed/{$request->tracking_token}");

    expect($request->fresh()->private_rating)->toBe(5);
    expect($request->fresh()->status)->toBe('feedback_received');

    Mail::assertQueued(PrivateFeedbackMail::class);
});

it('submitFeedback requires a rating between 1 and 5', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->post("/r/{$request->tracking_token}/feedback", ['rating' => 0])
        ->assertSessionHasErrors('rating');

    $this->post("/r/{$request->tracking_token}/feedback", ['rating' => 6])
        ->assertSessionHasErrors('rating');
});

it('submitFeedback is idempotent — does not overwrite if already submitted', function () {
    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'reviewed',
    ]);

    $this->post("/r/{$request->tracking_token}/feedback", [
        'rating' => 1,
        'feedback' => 'Should not overwrite',
    ]);

    // Status stays 'reviewed', not overwritten to 'feedback_received'
    expect($request->fresh()->status)->toBe('reviewed');
    expect($request->fresh()->private_rating)->toBeNull();
});

it('SendFollowUpRequests skips unverified_claim requests', function () {
    $this->business->update([
        'follow_up_enabled' => true,
        'follow_up_days' => 3,
        'follow_up_channel' => 'same',
    ]);

    ReviewRequest::factory()->unverifiedClaim()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'channel' => 'email',
        'sent_at' => now()->subDays(12),
    ]);

    (new SendFollowUpRequests)->handle();

    Mail::assertNothingQueued();
});
