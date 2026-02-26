<?php

namespace App\Models;

class LeaveType extends BaseModel
{
    protected $table = 'leave_types';

    protected $fillable = [
        'name',
        'isPaid',
        'annualQuota',
        'description',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'isPaid' => 'boolean',
            'isActive' => 'boolean',
            'annualQuota' => 'integer',
        ];
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'leaveTypeId');
    }
}
