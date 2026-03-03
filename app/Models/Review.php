<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'review_request_id',
        'rating',
        'body',
        'reviewer_name',
        'source',
        'reviewed_at',
        'google_review_id',
        'google_review_name',
        'google_reply',
        'google_reply_posted_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'google_reply_posted_at' => 'datetime',
    ];

    public function needsReply(): bool
    {
        return $this->google_review_name !== null && $this->google_reply === null;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewRequest(): BelongsTo
    {
        return $this->belongsTo(ReviewRequest::class);
    }

    public function stars(): string
    {
        return str_repeat('★', $this->rating).str_repeat('☆', 5 - $this->rating);
    }

    public function wasViaReviewMate(): bool
    {
        return $this->review_request_id !== null;
    }
}
