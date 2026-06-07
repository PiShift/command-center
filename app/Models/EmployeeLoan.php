<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id',
        'company_account_id',
        'title',
        'amount_total',
        'repayment_type',
        'repayment_value',
        'started_at',
        'ended_at',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount_total'     => 'decimal:2',
        'repayment_value'  => 'decimal:2',
        'started_at'       => 'date',
        'ended_at'         => 'date',
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

    public function repayments(): HasMany
    {
        return $this->hasMany(EmployeeLoanRepayment::class, 'loan_id');
    }

    public function getAmountRepaidAttribute(): float
    {
        return (float) $this->repayments()->sum('amount');
    }

    public function getAmountRemainingAttribute(): float
    {
        return (float) $this->amount_total - $this->amount_repaid;
    }

    public function getProgressPercentageAttribute(): int
    {
        if ((float) $this->amount_total <= 0) {
            return 0;
        }

        return (int) round(($this->amount_repaid / (float) $this->amount_total) * 100);
    }

    public function calculateNextInstallment(float $currentSalary): float
    {
        if ($this->repayment_type === 'fixed_amount') {
            return (float) $this->repayment_value;
        }

        return round($currentSalary * (((float) $this->repayment_value) / 100), 2);
    }

    public function isFullyRepaid(): bool
    {
        return $this->amount_remaining <= 0;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
