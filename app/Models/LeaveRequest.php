<?php

namespace App\Models;

use App\Notifications\LeaveRequestApprovedNotification;
use App\Notifications\LeaveRequestRejectedNotification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'days_actual',
        'status',
        'reason',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'days_requested' => 'decimal:1',
        'days_actual' => 'decimal:1',
        'status' => 'string',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForMonth($query, Carbon $month)
    {
        return $query->whereDate('start_date', '<=', $month->copy()->endOfMonth()->toDateString())
            ->whereDate('end_date', '>=', $month->copy()->startOfMonth()->toDateString());
    }

    public function getWorkingDaysAttribute(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0.0;
        }

        $days = 0;
        foreach (CarbonPeriod::create($this->start_date->copy()->startOfDay(), $this->end_date->copy()->startOfDay()) as $date) {
            if ($date->isWeekday()) {
                $days++;
            }
        }

        return (float) $days;
    }

    public function approve(User $approver): void
    {
        if ($this->status === 'approved') {
            return;
        }

        DB::transaction(function () use ($approver) {
            $this->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            $balance = EmployeeLeaveBalance::getOrCreate(
                (int) $this->employee_id,
                (int) $this->leave_type_id,
                (int) $this->start_date->year
            );

            $balance->increment('used_days', (float) ($this->days_actual ?? $this->days_requested ?? $this->working_days));
        });

        $fresh = $this->fresh(['employee.user', 'leaveType']);
        if ($fresh?->employee?->user) {
            $fresh->employee->user->notify(new LeaveRequestApprovedNotification($fresh));
        }
    }

    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $fresh = $this->fresh(['employee.user', 'leaveType']);
        if ($fresh?->employee?->user) {
            $fresh->employee->user->notify(new LeaveRequestRejectedNotification($fresh));
        }
    }

    public function cancel(): void
    {
        DB::transaction(function () {
            if ($this->status === 'approved') {
                $balance = EmployeeLeaveBalance::getOrCreate(
                    (int) $this->employee_id,
                    (int) $this->leave_type_id,
                    (int) $this->start_date->year
                );

                $restoreDays = (float) ($this->days_actual ?? $this->days_requested ?? $this->working_days);
                $balance->decrement('used_days', $restoreDays);
            }

            $this->update([
                'status' => 'cancelled',
            ]);
        });
    }
}
