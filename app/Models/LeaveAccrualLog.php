<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAccrualLog extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'month',
        'days_accrued',
        'accrued_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'days_accrued' => 'decimal:1',
        'accrued_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
