<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class CompanyBankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'currency',
        'usd_exchange_rate',
        'usd_exchange_rate_updated_at',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system' => 'boolean',
        'usd_exchange_rate' => 'decimal:6',
        'usd_exchange_rate_updated_at' => 'datetime',
    ];

    public function invoicePayments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class, 'company_account_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'company_account_id');
    }

    public function payrollRunPayments(): HasMany
    {
        return $this->hasMany(PayrollRunPayment::class, 'company_account_id');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function getBalanceAttribute(): float
    {
        // Money IN
        $paymentsIn = InvoicePayment::where('company_account_id', $this->id)
            ->sum('amount');

        // Money IN from transfers
        $transfersIn = Schema::hasTable('bank_account_transfers')
            ? (float) BankAccountTransfer::query()
                ->where('to_account_id', $this->id)
                ->selectRaw('COALESCE(SUM(COALESCE(amount_received, amount)), 0) as total')
                ->value('total')
            : 0.0;

        // Money OUT — expenses
        $expensesOut = Expense::where('company_account_id', $this->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        // Money OUT — advances
        $advancesOut = EmployeeAdvance::where('company_account_id', $this->id)
            ->sum('amount');

        // Money OUT — loans disbursed
        $loansOut = EmployeeLoan::where('company_account_id', $this->id)
            ->sum('amount_total');

        // Money OUT — payroll paid
        $payrollOut = Schema::hasTable('payroll_run_payments')
            ? (float) PayrollRunPayment::where('company_account_id', $this->id)->sum('amount')
            : (float) PayrollRun::where('company_account_id', $this->id)
                ->where('status', 'paid')
                ->sum('total_net');

        // Money OUT from transfers
        $transfersOut = Schema::hasTable('bank_account_transfers')
            ? (float) BankAccountTransfer::query()
                ->where('from_account_id', $this->id)
                ->selectRaw('COALESCE(SUM(COALESCE(amount_sent, amount)), 0) as total')
                ->value('total')
            : 0.0;

        return $paymentsIn + $transfersIn
            - $expensesOut - $advancesOut
            - $loansOut - $payrollOut - $transfersOut;
    }
}
