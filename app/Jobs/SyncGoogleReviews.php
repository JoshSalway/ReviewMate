<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Review;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

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

            Review::updateOrCreate(
                ['google_review_id' => $reviewId],
                [
                    'business_id'         => $this->business->id,
                    'rating'              => $rating,
                    'body'                => $data['comment'] ?? null,
                    'reviewer_name'       => $data['reviewer']['displayName'] ?? 'Anonymous',
                    'source'              => 'google',
                    'reviewed_at'         => isset($data['createTime'])
                        ? Carbon::parse($data['createTime'])
                        : now(),
                    'google_review_name'  => $data['name'] ?? null,
                    'google_reply'        => $data['reviewReply']['comment'] ?? null,
                    'google_reply_posted_at' => isset($data['reviewReply']['updateTime'])
                        ? Carbon::parse($data['reviewReply']['updateTime'])
                        : null,
                ]
            );
        }
    }
}
