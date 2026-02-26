<?php

namespace App\Models;

class ProductBatch extends BaseModel
{
    protected $table = 'product_batches';

    protected $fillable = [
        'productId',
        'productName',
        'batchNumber',
        'manufacturingDate',
        'expirationDate',
        'receivedDate',
        'initialQuantity',
        'currentQuantity',
        'reservedQuantity',
        'supplierId',
        'supplierName',
        'purchaseOrderId',
        'purchaseOrderNumber',
        'costPrice',
        'location',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'manufacturingDate' => 'date',
            'expirationDate' => 'date',
            'receivedDate' => 'date',
            'initialQuantity' => 'integer',
            'currentQuantity' => 'integer',
            'reservedQuantity' => 'integer',
            'costPrice' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchaseOrderId');
    }
}
