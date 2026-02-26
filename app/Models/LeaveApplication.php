<?php

namespace App\Models;

class LeaveApplication extends BaseModel
{
    protected $table = 'leave_applications';

    protected $fillable = [
        'employeeId',
        'employeeName',
        'leaveTypeId',
        'leaveTypeName',
        'startDate',
        'endDate',
        'days',
        'reason',
        'status',
        'approvedBy',
        'approvedAt',
        'rejectedReason',
    ];

    protected function casts(): array
    {
        return [
            'startDate' => 'date',
            'endDate' => 'date',
            'days' => 'decimal:1',
            'approvedAt' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeId');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leaveTypeId');
    }
}
