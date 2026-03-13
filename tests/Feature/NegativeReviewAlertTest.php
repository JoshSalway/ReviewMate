<?php

use App\Jobs\SyncGoogleReviews;
use App\Mail\NegativeReviewAlert;
use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\User;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Support\Facades\Mail;

/**
 * Feature 3: Negative Review Alert Email
 *
 * When SyncGoogleReviews pulls a 1-2 star review, immediately send a
 * NegativeReviewAlert email to the business owner.
 * Respects the 'negative_review_alert' notification preference.
 */
beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create([
        'notification_preferences' => [
            'new_review_alert' => false,
            'negative_review_alert' => true,
        ],
    ]);
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
    ]);
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'google',
        'access_token' => 'fake-token',
        'meta' => ['location_id' => 'accounts/123/locations/456'],
    ]);
});

test('syncing a 1-star review fires NegativeReviewAlert to the business owner', function () {
    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-1star',
            'name' => 'accounts/123/locations/456/reviews/review-1star',
            'starRating' => 'ONE',
            'comment' => 'Terrible experience.',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Angry Customer'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertQueued(NegativeReviewAlert::class, fn ($mail) => $mail->hasTo($this->user->email)
        && $mail->review->rating === 1
    );
});

test('syncing a 2-star review fires NegativeReviewAlert to the business owner', function () {
    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-2star',
            'name' => 'accounts/123/locations/456/reviews/review-2star',
            'starRating' => 'TWO',
            'comment' => 'Not great.',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Disappointed Customer'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertQueued(NegativeReviewAlert::class, fn ($mail) => $mail->hasTo($this->user->email)
        && $mail->review->rating === 2
    );
});

test('syncing a 3-star review does NOT fire NegativeReviewAlert', function () {
    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-3star',
            'name' => 'accounts/123/locations/456/reviews/review-3star',
            'starRating' => 'THREE',
            'comment' => 'It was okay.',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Average Customer'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertNotQueued(NegativeReviewAlert::class);
});

test('NegativeReviewAlert is not sent when user has disabled negative_review_alert preference', function () {
    $this->user->update([
        'notification_preferences' => [
            'new_review_alert' => false,
            'negative_review_alert' => false,
        ],
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-disabled',
            'name' => 'accounts/123/locations/456/reviews/review-disabled',
            'starRating' => 'ONE',
            'comment' => 'Awful!',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Unhappy Customer'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertNotQueued(NegativeReviewAlert::class);
});

test('4-star and 5-star reviews do not trigger NegativeReviewAlert', function () {
    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        [
            'reviewId' => 'review-4star',
            'name' => 'accounts/123/locations/456/reviews/review-4star',
            'starRating' => 'FOUR',
            'comment' => 'Pretty good!',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Happy Customer'],
        ],
        [
            'reviewId' => 'review-5star',
            'name' => 'accounts/123/locations/456/reviews/review-5star',
            'starRating' => 'FIVE',
            'comment' => 'Excellent!',
            'createTime' => now()->toIso8601String(),
            'reviewer' => ['displayName' => 'Very Happy Customer'],
        ],
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertNotQueued(NegativeReviewAlert::class);
});
