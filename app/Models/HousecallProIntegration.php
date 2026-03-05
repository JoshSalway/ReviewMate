<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousecallProIntegration extends Model
{
    protected $fillable = [
        'business_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'auto_send_reviews',
    ];

    protected $casts = [
        'access_token'      => 'encrypted',
        'refresh_token'     => 'encrypted',
        'token_expires_at'  => 'datetime',
        'auto_send_reviews' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isConnected(): bool
    {
        return filled($this->access_token);
    }
}
