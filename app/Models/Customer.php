<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'phone',
        'notes',
        'unsubscribed_at',
        'unsubscribe_token',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $model) {
            $model->unsubscribe_token ??= (string) Str::uuid();
        });
    }

    public function isUnsubscribed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function reviewRequests(): HasMany
    {
        return $this->hasMany(ReviewRequest::class);
    }

    public function latestReviewRequest(): HasOne
    {
        return $this->hasOne(ReviewRequest::class)->latestOfMany();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function requestStatus(): string
    {
        $latest = $this->latestReviewRequest;

        if (! $latest) {
            return 'no_request';
        }

        return $latest->status;
    }

    public function initials(): string
    {
        $parts = explode(' ', $this->name, 2);

        return strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    }
}
