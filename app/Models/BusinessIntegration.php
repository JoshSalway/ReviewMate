<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessIntegration extends Model
{
    protected $fillable = [
        'business_id',
        'provider',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'api_key',
        'auto_send_reviews',
        'last_polled_at',
        'meta',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'api_key' => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'auto_send_reviews' => 'boolean',
        'meta' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isConnected(): bool
    {
        return filled($this->access_token) || filled($this->api_key);
    }

    /** Get a meta value by key. */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return ($this->meta ?? [])[$key] ?? $default;
    }

    /** Set a meta value and save. */
    public function setMeta(string $key, mixed $value): void
    {
        $meta = $this->meta ?? [];
        $meta[$key] = $value;
        $this->update(['meta' => $meta]);
    }

    /** Merge meta values and save. */
    public function mergeMeta(array $values): void
    {
        $this->update(['meta' => array_merge($this->meta ?? [], $values)]);
    }
}
