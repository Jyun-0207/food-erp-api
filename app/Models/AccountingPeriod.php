<?php

namespace App\Models;

class AccountingPeriod extends BaseModel
{
    protected $table = 'accounting_periods';

    const UPDATED_AT = null;

    protected $fillable = [
        'periodType',
        'year',
        'month',
        'quarter',
        'startDate',
        'endDate',
        'status',
        'closedAt',
        'closedBy',
        'trialBalance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'startDate' => 'date',
            'endDate' => 'date',
            'closedAt' => 'datetime',
            'trialBalance' => 'array',
            'year' => 'integer',
            'month' => 'integer',
            'quarter' => 'integer',
        ];
    }
}
