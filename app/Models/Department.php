<?php

namespace App\Models;

class Department extends BaseModel
{
    protected $table = 'departments';

    protected $fillable = [
        'name',
        'managerId',
        'managerName',
        'description',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'isActive' => 'boolean',
        ];
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'departmentId');
    }
}
