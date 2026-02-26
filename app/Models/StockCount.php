<?php

namespace App\Models;

class StockCount extends BaseModel
{
    protected $table = 'stock_counts';

    const UPDATED_AT = null;

    protected $fillable = [
        'countNumber',
        'status',
        'items',
        'notes',
        'createdBy',
        'completedAt',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'completedAt' => 'datetime',
        ];
    }
}
