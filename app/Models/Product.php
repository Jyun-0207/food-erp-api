<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends BaseModel
{
    use SoftDeletes;

    const DELETED_AT = 'deletedAt';
    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'costPrice',
        'categoryId',
        'purchaseAccountId',
        'salesAccountId',
        'sku',
        'stock',
        'minStock',
        'unit',
        'images',
        'taxable',
        'purchasable',
        'supplierIds',
        'allergenIds',
        'categoryIds',
        'requiresBatch',
        'shelfLife',
        'shelfLifeUnit',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'costPrice' => 'decimal:2',
            'images' => 'array',
            'supplierIds' => 'array',
            'allergenIds' => 'array',
            'categoryIds' => 'array',
            'taxable' => 'boolean',
            'purchasable' => 'boolean',
            'requiresBatch' => 'boolean',
            'isActive' => 'boolean',
            'stock' => 'integer',
            'minStock' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryId');
    }

    public function purchaseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchaseAccountId');
    }

    public function salesAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'salesAccountId');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class, 'productId');
    }

    public function bom()
    {
        return $this->hasOne(Bom::class, 'productId');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'productId');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'productId');
    }
}
