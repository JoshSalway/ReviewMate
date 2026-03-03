<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'workos_id',
        'avatar',
        'is_admin',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'workos_id',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function notificationPreference(string $key, bool $default = true): bool
    {
        return (bool) ($this->notification_preferences[$key] ?? $default);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function onFreePlan(): bool
    {
        return ! $this->isAdmin() && ! $this->subscribed();
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function currentBusiness(): ?Business
    {
        $businessId = session('current_business_id');

        if ($businessId) {
            $business = $this->businesses()->find($businessId);
            if ($business) {
                return $business;
            }
        }

        return $this->businesses()->first();
    }

    public function switchBusiness(int $businessId): void
    {
        $business = $this->businesses()->findOrFail($businessId);
        session(['current_business_id' => $business->id]);
    }
}
