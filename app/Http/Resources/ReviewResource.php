<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'business_id' => $this->business_id,
            'customer_id' => $this->customer_id,
            'rating' => $this->rating,
            'body' => $this->body,
            'reviewer_name' => $this->reviewer_name,
            'google_review_id' => $this->google_review_id,
            'google_review_name' => $this->google_review_name,
            'google_reply' => $this->google_reply,
            'google_reply_posted_at' => $this->google_reply_posted_at?->toISOString(),
            'source' => $this->google_review_id ? 'google' : 'manual',
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
