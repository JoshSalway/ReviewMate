<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplyTemplate extends Model
{
    protected $fillable = ['business_id', 'name', 'body'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
