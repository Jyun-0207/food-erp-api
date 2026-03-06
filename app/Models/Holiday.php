<?php

namespace App\Models;

class Holiday extends BaseModel
{
    protected $table = 'holidays';

    protected $fillable = [
        'name',
        'date',
        'year',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'year' => 'integer',
        ];
    }
}
