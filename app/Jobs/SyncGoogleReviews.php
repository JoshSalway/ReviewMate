<?php

namespace App\Jobs;

use App\Mail\NewReviewAlertMail;
use App\Models\Business;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SyncGoogleReviews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Business $business) {}

    public function handle(GoogleBusinessProfileService $service): void
    {
        if (! $this->business->isGoogleConnected()) {
            return;
        }

        $reviews = $service->fetchReviews($this->business);

        foreach ($reviews as $data) {
            $reviewId = $data['reviewId'] ?? null;

            if (! $reviewId) {
                continue;
            }

            $starRatingMap = [
                'ONE' => 1,
                'TWO' => 2,
                'THREE' => 3,
                'FOUR' => 4,
                'FIVE' => 5,
            ];

            $rating = $starRatingMap[$data['starRating'] ?? ''] ?? null;

            if (! $rating) {
                continue;
            }

            [$review, $created] = [
                Review::updateOrCreate(
                    ['google_review_id' => $reviewId],
                    [
                        'business_id' => $this->business->id,
                        'rating' => $rating,
                        'body' => $data['comment'] ?? null,
                        'reviewer_name' => $data['reviewer']['displayName'] ?? 'Anonymous',
                        'source' => 'google',
                        'reviewed_at' => isset($data['createTime'])
                            ? Carbon::parse($data['createTime'])
                            : now(),
                        'google_review_name' => $data['name'] ?? null,
                        'google_reply' => $data['reviewReply']['comment'] ?? null,
                        'google_reply_posted_at' => isset($data['reviewReply']['updateTime'])
                            ? Carbon::parse($data['reviewReply']['updateTime'])
                            : null,
                    ]
                ),
                false,
            ];

            // wasRecentlyCreated is set by updateOrCreate
            if ($review->wasRecentlyCreated) {
                $this->linkToReviewRequest($review);

                $user = $this->business->user;
                if ($user && $user->notificationPreference('new_review_alert')) {
                    Mail::to($user->email, $user->name)
                        ->queue(new NewReviewAlertMail($user, $this->business, $review));
                }
            }
        }
    }

    /**
     * Try to match a newly synced Google review to a pending ReviewRequest using
     * fuzzy name matching (similar_text > 60%) against customers with pending requests
     * sent within the last 30 days.
     * When matched, link the review and close the request as reviewed.
     */
    protected function linkToReviewRequest(Review $review): void
    {
        if (! $review->reviewer_name || $review->reviewer_name === 'Anonymous') {
            return;
        }

        $candidates = ReviewRequest::query()
            ->where('business_id', $this->business->id)
            ->whereIn('status', ['sent', 'opened', 'followed_up'])
            ->whereNull('reviewed_at')
            ->where('sent_at', '>=', now()->subDays(30))
            ->with('customer')
            ->latest('sent_at')
            ->get();

        $bestRequest = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            if (! $candidate->customer) {
                continue;
            }

            similar_text(
                strtolower($review->reviewer_name),
                strtolower($candidate->customer->name),
                $percent
            );

            if ($percent > 60 && $percent > $bestScore) {
                $bestScore = $percent;
                $bestRequest = $candidate;
            }
        }

        if (! $bestRequest) {
            return;
        }

        // Link the review to this customer and request
        $review->update([
            'customer_id' => $bestRequest->customer_id,
            'review_request_id' => $bestRequest->id,
        ]);

        $bestRequest->markAsReviewed();
    }
}
