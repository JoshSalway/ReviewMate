<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'type',
        'subject',
        'body',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function renderBody(array $variables): string
    {
        $search = array_map(fn ($key) => "{{$key}}", array_keys($variables));
        $replace = array_values($variables);

        return str_replace($search, $replace, $this->body);
    }
}
