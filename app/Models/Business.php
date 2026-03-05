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
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'google_account_id',
        'google_location_id',
        'servicem8_access_token',
        'servicem8_refresh_token',
        'servicem8_token_expires_at',
        'servicem8_auto_send_reviews',
        // Xero integration
        'xero_access_token',
        'xero_refresh_token',
        'xero_token_expires_at',
        'xero_tenant_id',
        'xero_auto_send_reviews',
        // Cliniko integration
        'cliniko_api_key',
        'cliniko_shard',
        'cliniko_auto_send_reviews',
        'cliniko_last_polled_at',
        // Timely integration
        'timely_access_token',
        'timely_refresh_token',
        'timely_token_expires_at',
        'timely_account_id',
        'timely_auto_send_reviews',
        // Simpro integration
        'simpro_access_token',
        'simpro_refresh_token',
        'simpro_token_expires_at',
        'simpro_company_url',
        'simpro_auto_send_reviews',
        // Halaxy integration
        'halaxy_api_key',
        'halaxy_auto_send_reviews',
        'halaxy_last_polled_at',
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
        // Google Places stats cache
        'google_rating',
        'google_review_count',
        'google_stats_updated_at',
    ];

    protected $casts = [
        'onboarding_completed_at' => 'datetime',
        'google_access_token' => 'encrypted',
        'google_refresh_token' => 'encrypted',
        'servicem8_access_token' => 'encrypted',
        'servicem8_refresh_token' => 'encrypted',
        'servicem8_token_expires_at' => 'datetime',
        'servicem8_auto_send_reviews' => 'boolean',
        'xero_access_token' => 'encrypted',
        'xero_refresh_token' => 'encrypted',
        'xero_token_expires_at' => 'datetime',
        'xero_auto_send_reviews' => 'boolean',
        'cliniko_api_key' => 'encrypted',
        'cliniko_auto_send_reviews' => 'boolean',
        'cliniko_last_polled_at' => 'datetime',
        'timely_access_token' => 'encrypted',
        'timely_refresh_token' => 'encrypted',
        'timely_token_expires_at' => 'datetime',
        'timely_auto_send_reviews' => 'boolean',
        'simpro_access_token' => 'encrypted',
        'simpro_refresh_token' => 'encrypted',
        'simpro_token_expires_at' => 'datetime',
        'simpro_auto_send_reviews' => 'boolean',
        'halaxy_api_key' => 'encrypted',
        'halaxy_auto_send_reviews' => 'boolean',
        'halaxy_last_polled_at' => 'datetime',
        'follow_up_enabled' => 'boolean',
        'widget_enabled' => 'boolean',
        'google_rating' => 'decimal:2',
        'google_stats_updated_at' => 'datetime',
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

    public function replyTemplates(): HasMany
    {
        return $this->hasMany(ReplyTemplate::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_business_id');
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

        return '#';
    }

    public function facebookReviewUrl(): ?string
    {
        if (! $this->facebook_page_url) {
            return null;
        }

        return rtrim($this->facebook_page_url, '/') . '/reviews';
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
