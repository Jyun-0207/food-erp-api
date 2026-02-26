<?php

namespace App\Models;

class AccountingVoucher extends BaseModel
{
    protected $table = 'accounting_vouchers';

    protected $fillable = [
        'voucherNumber',
        'voucherType',
        'voucherDate',
        'lines',
        'totalDebit',
        'totalCredit',
        'description',
        'attachments',
        'reference',
        'status',
        'preparedBy',
        'preparedAt',
        'reviewedBy',
        'reviewedAt',
        'approvedBy',
        'approvedAt',
        'rejectedReason',
        'rejectedBy',
        'rejectedAt',
        'voidedBy',
        'voidedAt',
        'voidedReason',
    ];

    protected function casts(): array
    {
        return [
            'voucherDate' => 'date',
            'lines' => 'array',
            'attachments' => 'array',
            'totalDebit' => 'decimal:2',
            'totalCredit' => 'decimal:2',
            'preparedAt' => 'datetime',
            'reviewedAt' => 'datetime',
            'approvedAt' => 'datetime',
            'rejectedAt' => 'datetime',
            'voidedAt' => 'datetime',
        ];
    }
}
