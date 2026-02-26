<?php

namespace App\Models;

class ShiftType extends BaseModel
{
    protected $table = 'shift_types';

    protected $fillable = [
        'name',
        'startTime',
        'endTime',
        'breakStartTime',
        'breakEndTime',
        'graceMinutes',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'graceMinutes' => 'integer',
            'isActive' => 'boolean',
        ];
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'shiftTypeId');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'shiftTypeId');
    }
}
