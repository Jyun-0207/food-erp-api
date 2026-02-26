<?php

namespace App\Models;

class ChartOfAccount extends BaseModel
{
    protected $table = 'chart_of_accounts';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'type',
        'parentCode',
        'balance',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'isActive' => 'boolean',
        ];
    }

    public function purchaseProducts()
    {
        return $this->hasMany(Product::class, 'purchaseAccountId');
    }

    public function salesProducts()
    {
        return $this->hasMany(Product::class, 'salesAccountId');
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'accountId');
    }
}
