<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Expense extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'title', 'category_id', 'project_id', 'company_account_id', 'recurring_charge_id',
        'source_invoice_item_id',
        'amount', 'expense_date', 'status', 'notes',
        'currency', 'exchange_rate_used', 'amount_mru',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'amount_mru'   => 'decimal:2',
        'exchange_rate_used' => 'decimal:6',
        'expense_date' => 'date',
        'month'        => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Expense $expense) {
            if ($expense->expense_date) {
                $expense->month = Carbon::parse($expense->expense_date)->startOfMonth()->toDateString();
            }

            $accountCurrency = strtoupper((string) (
                $expense->companyAccount?->currency
                ?? CompanyBankAccount::query()->whereKey($expense->company_account_id)->value('currency')
            ));

            if ($accountCurrency === 'MRU' || $accountCurrency === '') {
                $expense->currency = 'MRU';
            }

            if (strtoupper((string) $expense->currency) === 'MRU') {
                $expense->exchange_rate_used = $expense->exchange_rate_used ?? 1;
            }

            if (is_null($expense->amount_mru) && ! is_null($expense->amount)) {
                $rate = (float) ($expense->exchange_rate_used ?? 1);
                $expense->amount_mru = round((float) $expense->amount * $rate, 2);
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // No conversions needed for receipts
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recurringCharge(): BelongsTo
    {
        return $this->belongsTo(RecurringCharge::class, 'recurring_charge_id');
    }

    public function companyAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_account_id');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeSumMruAmount(Builder $query): Builder
    {
        return $query->selectRaw('COALESCE(SUM(amount_mru), 0) as total_mru');
    }

    public function scopeForMonth($query, Carbon $month)
    {
        return $query->where('month', $month->copy()->startOfMonth()->toDateString());
    }

    public function originalCurrencyLabel(): string
    {
        if (strtoupper((string) $this->currency) === 'USD') {
            return '$'.number_format((float) $this->amount, 2);
        }

        return number_format((float) $this->amount, 2).' '.strtoupper((string) $this->currency);
    }
}
