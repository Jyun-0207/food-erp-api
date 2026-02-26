<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends BaseModel
{
    use SoftDeletes;

    const DELETED_AT = 'deletedAt';
    protected $table = 'purchase_orders';

    protected $fillable = [
        'orderNumber',
        'supplierId',
        'supplierName',
        'status',
        'items',
        'totalAmount',
        'paymentMethod',
        'expectedDate',
        'receivedDate',
        'returnedDate',
        'returnReason',
        'refundReceived',
        'notes',
        'createdBy',
        'approvedBy',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'totalAmount' => 'decimal:2',
            'expectedDate' => 'date',
            'receivedDate' => 'date',
            'returnedDate' => 'date',
            'refundReceived' => 'boolean',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class, 'purchaseOrderId');
    }

    public function accountsPayable()
    {
        return $this->hasMany(AccountsPayable::class, 'orderId');
    }
}
