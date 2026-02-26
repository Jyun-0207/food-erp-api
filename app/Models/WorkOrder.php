<?php

namespace App\Models;

class WorkOrder extends BaseModel
{
    protected $table = 'work_orders';

    protected $fillable = [
        'workOrderNumber',
        'productId',
        'productName',
        'quantity',
        'status',
        'scheduledDate',
        'completedDate',
        'bomId',
        'materialUsage',
        'qualityCheck',
        'notes',
        'createdBy',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'scheduledDate' => 'date',
            'completedDate' => 'date',
            'materialUsage' => 'array',
            'qualityCheck' => 'array',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bomId');
    }
}
