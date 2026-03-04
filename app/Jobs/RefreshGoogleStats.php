<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\GooglePlacesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshGoogleStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Business $business) {}

    public function handle(): void
    {
        $placeId = $this->business->google_place_id ?? null;

        if (! $placeId) {
            return;
        }

        $service = new GooglePlacesService;
        $stats = $service->getReviewStats($placeId);

        if ($stats) {
            $this->business->update([
                'google_rating' => $stats['rating'],
                'google_review_count' => $stats['review_count'],
                'google_stats_updated_at' => now(),
            ]);
        }
    }
}
