<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoanRepayment extends Model
{
    protected $fillable = [
        'loan_id',
        'payroll_entry_id',
        'amount',
        'salary_snapshot',
        'percentage_snapshot',
        'repayment_date',
        'notes',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'salary_snapshot'     => 'decimal:2',
        'percentage_snapshot' => 'decimal:2',
        'repayment_date'      => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'loan_id');
    }

    public function payrollEntry(): BelongsTo
    {
        return $this->belongsTo(PayrollEntry::class, 'payroll_entry_id');
    }
}
