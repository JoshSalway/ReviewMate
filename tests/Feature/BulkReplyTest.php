<?php

use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use App\Services\GoogleBusinessProfileService;

/**
 * Feature 4: Bulk Reply UI — backend tests
 *
 * POST /reviews/bulk-reply with an array of review IDs + reply text:
 *   1. All matching reviews are updated with google_reply + google_reply_posted_at
 *   2. Reviews belonging to another user's business are rejected
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);

    // Mock the Google service so we don't need real credentials
    $this->mockService = Mockery::mock(GoogleBusinessProfileService::class);
    $this->mockService->shouldReceive('postReply')->andReturn(null);
    app()->instance(GoogleBusinessProfileService::class, $this->mockService);
});

test('bulk reply updates all selected reviews with reply and google_reply_posted_at', function () {
    $reviews = Review::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/review-' . fake()->uuid(),
        'google_reply' => null,
        'rating' => 5,
    ]);

    // Give each a unique google_review_name
    $reviews->each(fn ($r, $i) => $r->update(['google_review_name' => "accounts/123/locations/456/reviews/review-{$i}"]));

    $this->post('/reviews/bulk-reply', [
        'review_ids' => $reviews->pluck('id')->toArray(),
        'reply' => 'Thank you for your review!',
    ])->assertRedirect('/reviews');

    foreach ($reviews as $review) {
        $fresh = $review->fresh();
        expect($fresh->google_reply)->toBe('Thank you for your review!');
        expect($fresh->google_reply_posted_at)->not->toBeNull();
    }
});

test('bulk reply cannot reply to reviews belonging to another business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = Business::factory()->onboarded()->create(['user_id' => $otherUser->id]);

    $otherReviews = Review::factory()->count(2)->create([
        'business_id' => $otherBusiness->id,
        'google_review_name' => 'accounts/other/locations/other/reviews/review-x',
        'google_reply' => null,
    ]);

    $this->post('/reviews/bulk-reply', [
        'review_ids' => $otherReviews->pluck('id')->toArray(),
        'reply' => 'Attempted reply',
    ])->assertStatus(422);

    // Reviews should not have been updated
    foreach ($otherReviews as $review) {
        expect($review->fresh()->google_reply)->toBeNull();
    }
});

test('bulk reply requires review_ids and reply fields', function () {
    $this->post('/reviews/bulk-reply', [])
        ->assertSessionHasErrors(['review_ids', 'reply']);
});

test('bulk reply skips reviews that already have a reply', function () {
    $alreadyReplied = Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/already-replied',
        'google_reply' => 'Existing reply',
    ]);

    $pending = Review::factory()->create([
        'business_id' => $this->business->id,
        'google_review_name' => 'accounts/123/locations/456/reviews/pending',
        'google_reply' => null,
    ]);

    $this->post('/reviews/bulk-reply', [
        'review_ids' => [$alreadyReplied->id, $pending->id],
        'reply' => 'Bulk reply text',
    ])->assertRedirect('/reviews');

    // Already-replied review should still have original reply
    expect($alreadyReplied->fresh()->google_reply)->toBe('Existing reply');

    // Pending review should now have the new reply
    expect($pending->fresh()->google_reply)->toBe('Bulk reply text');
});

test('bulk reply requires at least one review_id', function () {
    $this->post('/reviews/bulk-reply', [
        'review_ids' => [],
        'reply' => 'Some reply',
    ])->assertSessionHasErrors(['review_ids']);
});
