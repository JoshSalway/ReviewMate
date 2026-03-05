<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewRequestResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'business_id' => $this->business_id,
            'channel' => $this->channel,
            'status' => $this->status,
            'source' => $this->source,
            'followed_up_at' => $this->followed_up_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
