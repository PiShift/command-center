<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'carried_over_days',
    ];

    protected $casts = [
        'allocated_days' => 'decimal:1',
        'used_days' => 'decimal:1',
        'carried_over_days' => 'decimal:1',
        'year' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function getRemainingDaysAttribute(): float
    {
        return (float) $this->allocated_days + (float) $this->carried_over_days - (float) $this->used_days;
    }

    public static function getOrCreate(int $employeeId, int $leaveTypeId, int $year): self
    {
        return static::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            [
                'allocated_days' => 0,
                'used_days' => 0,
                'carried_over_days' => 0,
            ]
        );
    }
}
