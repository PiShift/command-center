<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'month',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'company_account_id',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'month'            => 'date',
        'paid_at'          => 'datetime',
        'total_gross'      => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net'        => 'decimal:2',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class, 'payroll_run_id')->with(['employee', 'contract']);
    }

    public function companyAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function recalculateTotals(): void
    {
        $totalGross = (float) $this->entries()->sum('gross_amount');
        $entries = $this->entries()->get(['advances_deducted', 'loans_deducted', 'other_deductions', 'unpaid_leave_deduction', 'skip_advances', 'skip_loans', 'skip_unpaid_leave']);
        $totalDeductions = (float) $entries->sum(function (PayrollEntry $entry) {
            $advances = (bool) $entry->skip_advances ? 0 : (float) $entry->advances_deducted;
            $loans = (bool) $entry->skip_loans ? 0 : (float) $entry->loans_deducted;
            $otherDeductions = (float) $entry->other_deductions;

            if ($entry->skip_unpaid_leave) {
                $otherDeductions -= (float) $entry->unpaid_leave_deduction;
            }

            return $advances + $loans + max(0, $otherDeductions);
        });
        $totalNet = (float) $this->entries()->sum('net_amount');

        $this->update([
            'total_gross' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net' => $totalNet,
        ]);
    }
}
