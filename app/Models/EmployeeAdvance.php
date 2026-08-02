<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvance extends Model
{
    protected $fillable = [
        'employee_id',
        'company_account_id',
        'amount',
        'date',
        'reason',
        'status',
        'payroll_entry_id',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date'   => 'date',
        'status' => 'string',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function companyAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_account_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDeducted(Builder $query): Builder
    {
        return $query->where('status', 'deducted');
    }

    public function markDeducted(int $payrollEntryId): void
    {
        $this->update([
            'status'           => 'deducted',
            'payroll_entry_id' => $payrollEntryId,
        ]);
    }
}
