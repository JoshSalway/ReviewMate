<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'google_place_id',
        'owner_name',
        'phone',
        'onboarding_completed_at',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'google_account_id',
        'google_location_id',
    ];

    protected $casts = [
        'onboarding_completed_at' => 'datetime',
        'google_access_token' => 'encrypted',
        'google_refresh_token' => 'encrypted',
    ];

    public function isGoogleConnected(): bool
    {
        return $this->google_access_token !== null && $this->google_location_id !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function reviewRequests(): HasMany
    {
        return $this->hasMany(ReviewRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    public function googleReviewUrl(): string
    {
        if ($this->google_place_id) {
            return "https://search.google.com/local/writereview?placeid={$this->google_place_id}";
        }

        return '#';
    }

    public function isOnboardingComplete(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function averageRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function conversionRate(): float
    {
        $sent = $this->reviewRequests()->count();

        if ($sent === 0) {
            return 0;
        }

        $reviewed = $this->reviewRequests()->where('status', 'reviewed')->count();

        return round(($reviewed / $sent) * 100);
    }
}
