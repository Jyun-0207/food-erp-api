<?php

namespace App\Models;

class AttendanceRecord extends BaseModel
{
    protected $table = 'attendance_records';

    protected $fillable = [
        'employeeId',
        'employeeName',
        'date',
        'shiftTypeId',
        'shiftTypeName',
        'checkInTime',
        'checkOutTime',
        'workHours',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'checkInTime' => 'datetime',
            'checkOutTime' => 'datetime',
            'workHours' => 'float',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeId');
    }

    public function shiftType()
    {
        return $this->belongsTo(ShiftType::class, 'shiftTypeId');
    }
}
