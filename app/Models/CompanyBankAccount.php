<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyBankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'currency',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system'  => 'boolean',
    ];

    public function invoicePayments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class, 'company_account_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'company_account_id');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function getBalanceAttribute(): float
    {
        $paymentsIn = (float) $this->invoicePayments()->sum('amount');

        $expensesOut = (float) Expense::where('company_account_id', $this->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        return $paymentsIn - $expensesOut;
    }
}
