<?php

namespace App\Models;

class SupplierPriceList extends BaseModel
{
    protected $table = 'supplier_price_lists';

    protected $fillable = [
        'supplierId',
        'supplierName',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }
}
