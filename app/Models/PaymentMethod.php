<?php

namespace App\Models;

class PaymentMethod extends BaseModel
{
    protected $table = 'payment_methods';

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'isActive',
        'requiresOnlinePayment',
        'type',
        'cvsType',
        'accountId',
    ];

    protected function casts(): array
    {
        return [
            'isActive' => 'boolean',
            'requiresOnlinePayment' => 'boolean',
        ];
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'accountId');
    }
}
