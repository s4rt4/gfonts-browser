<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FontFile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_variable' => 'boolean',
        'weight' => 'integer',
        'file_size' => 'integer',
    ];

    public function fontFamily(): BelongsTo
    {
        return $this->belongsTo(FontFamily::class);
    }
}
