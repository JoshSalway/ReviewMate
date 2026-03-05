<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_business_id',
        'referred_customer_id',
        'referral_token',
        'referral_type',
        'status',
        'referred_business_id',
        'signed_up_at',
        'converted_at',
        'reward_issued_at',
    ];

    protected $casts = [
        'signed_up_at' => 'datetime',
        'converted_at' => 'datetime',
        'reward_issued_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Referral $model) {
            $model->referral_token ??= Str::random(24);
        });
    }

    public function referrerBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'referrer_business_id');
    }

    public function referredCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_customer_id');
    }

    public function referredBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'referred_business_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }
}
