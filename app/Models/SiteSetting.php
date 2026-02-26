<?php

namespace App\Models;

class SiteSetting extends BaseModel
{
    protected $table = 'site_settings';

    const CREATED_AT = null;

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
