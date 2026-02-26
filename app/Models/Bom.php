<?php

namespace App\Models;

class Bom extends BaseModel
{
    protected $table = 'boms';

    protected $fillable = [
        'productId',
        'productName',
        'items',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'bomId');
    }
}
