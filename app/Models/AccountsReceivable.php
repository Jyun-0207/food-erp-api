<?php

namespace App\Models;

class AccountsReceivable extends BaseModel
{
    protected $table = 'accounts_receivable';

    const UPDATED_AT = null;

    protected $fillable = [
        'customerId',
        'customerName',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'orderId');
    }
}
