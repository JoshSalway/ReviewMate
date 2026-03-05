<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

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
        'role',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin();
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
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
