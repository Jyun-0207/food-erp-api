<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends BaseModel
{
    use SoftDeletes;

    const DELETED_AT = 'deletedAt';
    protected $table = 'employees';

    protected $fillable = [
        'employeeNumber',
        'name',
        'phone',
        'email',
        'pinCode',
        'departmentId',
        'departmentName',
        'position',
        'hireDate',
        'resignDate',
        'status',
        'shiftTypeId',
        'shiftTypeName',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hireDate' => 'date',
            'resignDate' => 'date',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'departmentId');
    }

    public function shiftType()
    {
        return $this->belongsTo(ShiftType::class, 'shiftTypeId');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'employeeId');
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'employeeId');
    }
}
