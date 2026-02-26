<?php

namespace App\Models;

class InventoryMovement extends BaseModel
{
    protected $table = 'inventory_movements';

    const UPDATED_AT = null;

    protected $fillable = [
        'productId',
        'productName',
        'type',
        'quantity',
        'beforeStock',
        'afterStock',
        'reason',
        'reference',
        'createdBy',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'beforeStock' => 'integer',
            'afterStock' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }
}
