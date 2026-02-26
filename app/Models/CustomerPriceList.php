<?php

namespace App\Models;

class CustomerPriceList extends BaseModel
{
    protected $table = 'customer_price_lists';

    protected $fillable = [
        'customerId',
        'customerName',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }
}
