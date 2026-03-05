<?php

use App\Jobs\SyncGoogleReviews;
use App\Mail\NewReviewAlertMail;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create(['notification_preferences' => ['new_review_alert' => true]]);
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
        'google_access_token' => 'fake-token',
        'google_location_id' => 'accounts/123/locations/456',
    ]);
});

function fakeReviewData(array $overrides = []): array
{
    return array_merge([
        'reviewId' => 'review-abc',
        'name' => 'accounts/123/locations/456/reviews/review-abc',
        'starRating' => 'FIVE',
        'comment' => 'Excellent service!',
        'createTime' => now()->toIso8601String(),
        'reviewer' => ['displayName' => 'Jane Smith'],
    ], $overrides);
}

test('sync creates new reviews from google', function () {
    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')
        ->once()
        ->andReturn([fakeReviewData()]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect(Review::where('google_review_id', 'review-abc')->exists())->toBeTrue();
});

test('sync does not duplicate existing reviews', function () {
    Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_id' => 'review-abc',
        'rating' => 4,
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')
        ->once()
        ->andReturn([fakeReviewData(['starRating' => 'FIVE'])]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect(Review::where('google_review_id', 'review-abc')->count())->toBe(1);
    // Rating updated from 4 → 5
    expect(Review::where('google_review_id', 'review-abc')->value('rating'))->toBe(5);
});

test('sync sends new review alert email for newly created reviews', function () {
    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([fakeReviewData()]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertQueued(NewReviewAlertMail::class, fn ($mail) => $mail->hasTo($this->user->email)
    );
});

test('sync does not send alert for existing reviews', function () {
    Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_id' => 'review-abc',
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([fakeReviewData()]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertNothingQueued();
});

test('sync does not send alert when user has disabled new review alerts', function () {
    $this->user->update(['notification_preferences' => ['new_review_alert' => false]]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([fakeReviewData()]);

    (new SyncGoogleReviews($this->business))->handle($service);

    Mail::assertNothingQueued();
});

test('sync links new review to matching pending review request', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Smith',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(3),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['reviewer' => ['displayName' => 'Jane Smith']]),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    $review = Review::where('google_review_id', 'review-abc')->first();
    expect($review->customer_id)->toBe($customer->id);
    expect($review->review_request_id)->toBe($request->id);

    expect($request->fresh()->status)->toBe('reviewed');
    expect($request->fresh()->reviewed_at)->not->toBeNull();
});

test('sync match is case-insensitive', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'jane smith',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(2),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['reviewer' => ['displayName' => 'Jane Smith']]),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect($request->fresh()->status)->toBe('reviewed');
});

test('sync does not match already reviewed requests', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Smith',
    ]);

    $request = ReviewRequest::factory()->reviewed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'sent_at' => now()->subDays(3),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['reviewId' => 'review-new', 'reviewer' => ['displayName' => 'Jane Smith']]),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    // Request stays reviewed, review has no customer link since request was already closed
    $review = Review::where('google_review_id', 'review-new')->first();
    expect($review->review_request_id)->toBeNull();
});

test('sync skips reviews with no star rating', function () {
    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['starRating' => 'RATING_UNSPECIFIED']),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect(Review::where('business_id', $this->business->id)->count())->toBe(0);
});

test('sync skips business not connected to google', function () {
    $disconnectedBusiness = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldNotReceive('fetchReviews');

    (new SyncGoogleReviews($disconnectedBusiness))->handle($service);
});

test('sync fuzzy-matches reviewer name with similar_text above 60 percent', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jonathan Williams',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(5),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    // Reviewer used a shortened name — similar_text should be >60%
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['reviewId' => 'review-fuzzy', 'reviewer' => ['displayName' => 'Jonathan Williams']]),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect($request->fresh()->status)->toBe('reviewed');
});

test('sync does not match reviewer names with less than 60 percent similarity', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Alice Brown',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(5),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    // Completely different name — should not match
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['reviewId' => 'review-nomatch', 'reviewer' => ['displayName' => 'Xavier Thompson']]),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    $review = Review::where('google_review_id', 'review-nomatch')->first();
    expect($review->customer_id)->toBeNull();
    expect($request->fresh()->status)->toBe('sent');
});

test('sync matches followed_up status requests', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Mary Johnson',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'followed_up',
        'sent_at' => now()->subDays(7),
        'followed_up_at' => now()->subDays(4),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['reviewId' => 'review-followedup', 'reviewer' => ['displayName' => 'Mary Johnson']]),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    expect($request->fresh()->status)->toBe('reviewed');
});

test('sync does not match requests sent more than 30 days ago', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Old Customer',
    ]);

    $request = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(35),
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('fetchReviews')->andReturn([
        fakeReviewData(['reviewId' => 'review-old', 'reviewer' => ['displayName' => 'Old Customer']]),
    ]);

    (new SyncGoogleReviews($this->business))->handle($service);

    $review = Review::where('google_review_id', 'review-old')->first();
    expect($review->customer_id)->toBeNull();
    expect($request->fresh()->status)->toBe('sent');
});
