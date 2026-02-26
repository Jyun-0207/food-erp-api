<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends BaseModel
{
    use SoftDeletes;

    const DELETED_AT = 'deletedAt';
    protected $table = 'sales_orders';

    protected $fillable = [
        'orderNumber',
        'customerId',
        'customerName',
        'status',
        'items',
        'subtotal',
        'tax',
        'shipping',
        'totalAmount',
        'shippingAddress',
        'paymentMethod',
        'paymentStatus',
        'notes',
        'returnedDate',
        'returnReason',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'shippingAddress' => 'array',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'totalAmount' => 'decimal:2',
            'returnedDate' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function accountsReceivable()
    {
        return $this->hasMany(AccountsReceivable::class, 'orderId');
    }
}
