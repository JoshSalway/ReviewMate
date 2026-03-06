<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uuid',
        'name',
        'type',
        'google_place_id',
        'owner_name',
        'phone',
        'onboarding_completed_at',
        // Follow-up settings
        'follow_up_enabled',
        'follow_up_days',
        'follow_up_channel',
        // Widget settings
        'widget_enabled',
        'widget_min_rating',
        'widget_max_reviews',
        'widget_theme',
        'slug',
        // Referral
        'referral_token',
        // Generic incoming webhook
        'webhook_token',
        // Review platforms
        'facebook_page_url',
        // Auto-reply settings
        'auto_reply_enabled',
        'auto_reply_min_rating',
        'auto_reply_tone',
        'auto_reply_length',
        'auto_reply_signature',
        'auto_reply_custom_instructions',
        // Timezone & run stats
        'timezone',
        'auto_reply_last_run_at',
        'auto_reply_last_reply_count',
    ];

    protected $casts = [
        'onboarding_completed_at' => 'datetime',
        'follow_up_enabled' => 'boolean',
        'widget_enabled' => 'boolean',
        'auto_reply_enabled' => 'boolean',
        'auto_reply_last_run_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Business $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->webhook_token ??= Str::random(40);
        });

        static::created(function (Business $model) {
            $updates = [];

            if (! $model->slug) {
                $base = Str::slug($model->name);
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->where('id', '!=', $model->id)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $updates['slug'] = $slug;
            }

            if (! $model->referral_token) {
                $updates['referral_token'] = Str::random(16);
            }

            if (! empty($updates)) {
                $model->updateQuietly($updates);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isGoogleConnected(): bool
    {
        $google = $this->integration('google');

        return $google?->isConnected() && filled($google->getMeta('location_id'));
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

    public function replyTemplates(): HasMany
    {
        return $this->hasMany(ReplyTemplate::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_business_id');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(BusinessIntegration::class);
    }

    public function integration(string $provider): ?BusinessIntegration
    {
        // Use already-loaded collection if available, otherwise query
        if ($this->relationLoaded('integrations')) {
            return $this->integrations->firstWhere('provider', $provider);
        }

        return $this->integrations()->where('provider', $provider)->first();
    }

    public function referralUrl(): string
    {
        return url('/r/ref/'.$this->referral_token);
    }

    public function googleReviewUrl(): string
    {
        if ($this->google_place_id) {
            return "https://search.google.com/local/writereview?placeid={$this->google_place_id}";
        }

        // Fallback: Google search for the business by name
        return 'https://www.google.com/search?q='.urlencode($this->name.' reviews');
    }

    public function facebookReviewUrl(): ?string
    {
        if (! $this->facebook_page_url) {
            return null;
        }

        return rtrim($this->facebook_page_url, '/').'/reviews';
    }

    public function hasFacebookReviews(): bool
    {
        return filled($this->facebook_page_url);
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
