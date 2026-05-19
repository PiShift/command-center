<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class MonthlyBudget extends Model
{
    protected $fillable = ['category_id', 'month', 'amount', 'notes'];

    protected $casts = [
        'month'  => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Returns all budget allocations for a given month, keyed by category_id.
     */
    public static function getForMonth(Carbon $month): Collection
    {
        return static::where('month', $month->copy()->startOfMonth()->toDateString())
            ->get()
            ->keyBy('category_id');
    }
}
