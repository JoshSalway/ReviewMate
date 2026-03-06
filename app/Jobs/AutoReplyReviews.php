<?php

namespace App\Jobs;

use App\Ai\Agents\ReviewReplyAgent;
use App\Models\Business;
use App\Models\Review;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AutoReplyReviews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly Business $business) {}

    public function handle(GoogleBusinessProfileService $googleService): void
    {
        if (! $this->business->auto_reply_enabled) {
            return;
        }

        if (! $this->business->isGoogleConnected()) {
            return;
        }

        $owner = $this->business->user;

        if (! $owner || $owner->onFreePlan()) {
            return;
        }

        $agent = new ReviewReplyAgent(
            businessName: $this->business->name,
            businessType: $this->business->type,
            ownerName: $this->business->owner_name ?? $owner->name,
            tone: $this->business->auto_reply_tone ?? 'friendly',
            length: $this->business->auto_reply_length ?? 'medium',
            signature: $this->business->auto_reply_signature,
            customInstructions: $this->business->auto_reply_custom_instructions,
            multipleOptions: false,
        );

        $reviews = $this->business->reviews()
            ->whereNotNull('google_review_name')
            ->whereNull('google_reply')
            ->whereNull('auto_replied_at')
            ->where('rating', '>=', $this->business->auto_reply_min_rating ?? 4)
            ->whereNotNull('body')
            ->latest('reviewed_at')
            ->limit(20)
            ->get();

        foreach ($reviews as $review) {
            $this->replyToReview($agent, $googleService, $review);
        }
    }

    private function replyToReview(
        ReviewReplyAgent $agent,
        GoogleBusinessProfileService $googleService,
        Review $review,
    ): void {
        try {
            $response = $agent->prompt(
                "Generate a reply for this {$review->rating}-star Google review:\n\n\"{$review->body}\""
            );

            $replyText = trim($response->text);

            if (empty($replyText)) {
                return;
            }

            $googleService->postReply(
                $this->business,
                $review->google_review_name,
                $replyText,
            );

            $review->update([
                'google_reply' => $replyText,
                'google_reply_posted_at' => now(),
                'auto_replied_at' => now(),
            ]);
        } catch (Throwable) {
            // Don't fail the whole job if one review fails
        }
    }
}
