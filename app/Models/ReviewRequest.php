<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReviewRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'status',
        'channel',
        'sent_at',
        'opened_at',
        'reviewed_at',
        'followed_up_at',
        'tracking_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReviewRequest $model) {
            $model->tracking_token ??= (string) Str::uuid();
        });
    }

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'followed_up_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function markAsOpened(): void
    {
        $this->update([
            'status' => 'opened',
            'opened_at' => now(),
        ]);
    }

    public function markAsReviewed(): void
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
        ]);
    }
}
