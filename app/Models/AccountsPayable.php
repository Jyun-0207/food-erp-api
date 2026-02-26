<?php

namespace App\Models;

class AccountsPayable extends BaseModel
{
    protected $table = 'accounts_payable';

    const UPDATED_AT = null;

    protected $fillable = [
        'supplierId',
        'supplierName',
        'orderId',
        'orderNumber',
        'invoiceNumber',
        'amount',
        'paidAmount',
        'dueDate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paidAmount' => 'decimal:2',
            'dueDate' => 'date',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'orderId');
    }
}
