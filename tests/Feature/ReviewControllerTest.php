<?php

use App\Models\Business;
use App\Models\BusinessIntegration;
use App\Models\Customer;
use App\Models\Review;
use App\Models\ReplyTemplate;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access reviews page', function () {
    auth()->logout();
    $this->get('/reviews')->assertRedirect('/login');
});

test('user with completed onboarding can view reviews', function () {
    $this->get('/reviews')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reviews/index')
            ->has('needsReply')
            ->has('replied')
            ->has('allReviews')
            ->has('isGoogleConnected')
        );
});

test('user without completed onboarding is redirected from reviews', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/reviews')
        ->assertRedirect(route('onboarding.business-type'));
});

test('reviews page shows is google connected as false when not connected', function () {
    $this->get('/reviews')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isGoogleConnected', false)
        );
});

test('user can view a single review', function () {
    $review = Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => 'Great service!',
        'reviewer_name' => 'John',
    ]);

    $this->get("/reviews/{$review->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reviews/show')
            ->where('review.id', $review->id)
            ->where('review.rating', 5)
            ->where('review.body', 'Great service!')
            ->where('review.reviewer_name', 'John')
        );
});

test('user cannot view a review from another business', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $review = Review::factory()->create(['business_id' => $otherBusiness->id]);

    $this->get("/reviews/{$review->id}")
        ->assertForbidden();
});

test('review show page includes reply templates', function () {
    ReplyTemplate::create(['business_id' => $this->business->id, 'name' => 'T1', 'body' => 'Body 1']);
    ReplyTemplate::create(['business_id' => $this->business->id, 'name' => 'T2', 'body' => 'Body 2']);
    $review = Review::factory()->create(['business_id' => $this->business->id]);

    $this->get("/reviews/{$review->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('replyTemplates', 2)
        );
});

test('posting a reply to a review requires a google review name', function () {
    $review = Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_name' => null,
    ]);

    $this->post("/reviews/{$review->id}/reply", ['reply' => 'Thank you!'])
        ->assertStatus(422);
});

test('user cannot post reply to review from another business', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $review = Review::factory()->create([
        'business_id' => $otherBusiness->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/abc',
    ]);

    $this->post("/reviews/{$review->id}/reply", ['reply' => 'Thank you!'])
        ->assertForbidden();
});

test('post reply requires reply field', function () {
    $review = Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/abc',
    ]);

    $this->post("/reviews/{$review->id}/reply", [])
        ->assertSessionHasErrors('reply');
});

test('posting a reply saves it and posts to google', function () {
    BusinessIntegration::create([
        'business_id' => $this->business->id,
        'provider' => 'google',
        'access_token' => 'fake-token',
        'meta' => ['location_id' => 'accounts/123/locations/456'],
    ]);

    $review = Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/abc',
        'google_reply' => null,
    ]);

    $service = Mockery::mock(GoogleBusinessProfileService::class);
    $service->shouldReceive('postReply')->once()->with(
        Mockery::any(),
        'accounts/123/locations/456/reviews/abc',
        'Thank you for the feedback!'
    );
    app()->instance(GoogleBusinessProfileService::class, $service);

    $this->post("/reviews/{$review->id}/reply", ['reply' => 'Thank you for the feedback!'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($review->fresh()->google_reply)->toBe('Thank you for the feedback!');
    expect($review->fresh()->google_reply_posted_at)->not->toBeNull();
});

test('reviews are partitioned by needs-reply vs replied vs no-google-link', function () {
    // Needs reply: has google name, no reply
    Review::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/aaa',
        'google_reply' => null,
    ]);

    // Replied: has google name and reply
    Review::factory()->count(1)->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/bbb',
        'google_reply' => 'Thanks!',
    ]);

    // All reviews: no google link
    Review::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'google_review_name' => null,
    ]);

    $this->get('/reviews')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('needsReply.data', 2)
            ->has('replied.data', 1)
            ->has('allReviews.data', 3)
        );
});

test('review show exposes wasViaReviewMate as true when review request is linked', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $reviewRequest = ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
    ]);
    $review = Review::factory()->create([
        'business_id' => $this->business->id,
        'review_request_id' => $reviewRequest->id,
    ]);

    $this->get("/reviews/{$review->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('review.via_review_mate', true)
        );
});

test('review show exposes wasViaReviewMate as false when not linked', function () {
    $review = Review::factory()->create([
        'business_id' => $this->business->id,
        'review_request_id' => null,
    ]);

    $this->get("/reviews/{$review->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('review.via_review_mate', false)
        );
});
