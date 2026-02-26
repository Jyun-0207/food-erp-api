<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends BaseModel
{
    use SoftDeletes;

    const DELETED_AT = 'deletedAt';
    protected $table = 'customers';

    protected $attributes = [
        'address' => '{}',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'companyName',
        'taxId',
        'creditLimit',
        'paymentTerms',
        'address',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'creditLimit' => 'decimal:2',
            'address' => 'array',
            'isActive' => 'boolean',
        ];
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'customerId');
    }

    public function customerPriceList()
    {
        return $this->hasOne(CustomerPriceList::class, 'customerId');
    }

    public function accountsReceivable()
    {
        return $this->hasMany(AccountsReceivable::class, 'customerId');
    }
}
