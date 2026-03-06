<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\BusinessIntegration;
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
            $integration = BusinessIntegration::firstOrCreate(
                ['business_id' => $this->business->id, 'provider' => 'google']
            );
            $integration->mergeMeta([
                'rating' => $stats['rating'],
                'review_count' => $stats['review_count'],
                'stats_updated_at' => now()->toDateTimeString(),
            ]);
        }
    }
}
