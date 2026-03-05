<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type,
            'owner_name' => $this->owner_name,
            'phone' => $this->phone,
            'google_place_id' => $this->google_place_id,
            'google_connected' => $this->isGoogleConnected(),
            'google_rating' => $this->integration('google')?->getMeta('rating'),
            'google_review_count' => $this->integration('google')?->getMeta('review_count'),
            'average_rating' => $this->averageRating(),
            'conversion_rate' => $this->conversionRate(),
            'onboarding_completed_at' => $this->onboarding_completed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
