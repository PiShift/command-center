<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'color',
        'is_paid',
        'requires_approval',
        'accrues_monthly',
        'monthly_accrual_days',
        'default_days_per_year',
        'is_active',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'accrues_monthly' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'monthly_accrual_days' => 'decimal:1',
    ];

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class, 'leave_type_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
