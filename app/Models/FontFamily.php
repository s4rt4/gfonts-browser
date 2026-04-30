<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FontFamily extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subsets' => 'array',
        'axes' => 'array',
        'designers' => 'array',
        'languages' => 'array',
        'classifications' => 'array',
        'color_capabilities' => 'array',
        'is_noto' => 'boolean',
        'is_brand_font' => 'boolean',
        'is_open_source' => 'boolean',
        'is_variable' => 'boolean',
        'date_added' => 'date',
        'last_modified' => 'date',
    ];

    public function fontFiles(): HasMany
    {
        return $this->hasMany(FontFile::class)->orderBy('weight')->orderBy('style');
    }

    public function getRouteKeyName(): string
    {
        return 'family';
    }
}
