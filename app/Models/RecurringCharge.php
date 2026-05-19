<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringCharge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'category_id', 'project_id', 'amount', 'frequency',
        'start_date', 'next_due_date', 'end_date', 'max_occurrences', 'occurrences_count',
        'is_active', 'notes',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'start_date'        => 'date',
        'next_due_date'     => 'date',
        'end_date'          => 'date',
        'max_occurrences'   => 'integer',
        'occurrences_count' => 'integer',
        'is_active'         => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Advance next_due_date by one frequency period and save.
     */
    public function computeNextDueDate(): void
    {
        $this->next_due_date = match ($this->frequency) {
            'monthly'   => $this->next_due_date->addMonth(),
            'quarterly' => $this->next_due_date->addMonths(3),
            'annual'    => $this->next_due_date->addMonths(12),
        };
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueInMonth($query, Carbon $month)
    {
        return $query->whereBetween('next_due_date', [
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth(),
        ]);
    }
}
