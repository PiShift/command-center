<?php

namespace App\Models;

use Carbon\Carbon;
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
        'title', 'category_id', 'project_id', 'recurring_charge_id',
        'amount', 'expense_date', 'status', 'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
        'month'        => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Expense $expense) {
            if ($expense->expense_date) {
                $expense->month = Carbon::parse($expense->expense_date)->startOfMonth()->toDateString();
            }
            // Currency is always MRU — never accept from request
            $expense->currency = 'MRU';
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

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeForMonth($query, Carbon $month)
    {
        return $query->where('month', $month->copy()->startOfMonth()->toDateString());
    }
}
