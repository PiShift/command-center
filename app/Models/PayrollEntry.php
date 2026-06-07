<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PayrollEntry extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'contract_id',
        'base_salary',
        'gross_amount',
        'advances_deducted',
        'skip_advances',
        'loans_deducted',
        'skip_loans',
        'skip_unpaid_leave',
        'other_deductions',
        'unpaid_leave_deduction',
        'bonuses',
        'net_amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'base_salary'        => 'decimal:2',
        'gross_amount'       => 'decimal:2',
        'advances_deducted'  => 'decimal:2',
        'skip_advances'      => 'boolean',
        'loans_deducted'     => 'decimal:2',
        'skip_loans'         => 'boolean',
        'skip_unpaid_leave'  => 'boolean',
        'other_deductions'   => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'bonuses'            => 'decimal:2',
        'net_amount'         => 'decimal:2',
        'paid_at'            => 'datetime',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EmployeeContract::class, 'contract_id');
    }

    public function advances(): HasMany
    {
        return $this->hasMany(EmployeeAdvance::class, 'payroll_entry_id');
    }

    public function loanRepayments(): HasMany
    {
        return $this->hasMany(EmployeeLoanRepayment::class, 'payroll_entry_id');
    }

    public function getTotalDeductionsAttribute(): float
    {
        $otherDeductions = (float) $this->other_deductions;
        if ($this->skip_unpaid_leave) {
            $otherDeductions -= (float) $this->unpaid_leave_deduction;
        }

        return ($this->skip_advances ? 0.0 : (float) $this->advances_deducted)
            + ($this->skip_loans ? 0.0 : (float) $this->loans_deducted)
            + max(0, $otherDeductions);
    }
}
