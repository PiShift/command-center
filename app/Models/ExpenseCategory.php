<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'color', 'icon', 'sort_order'];

    public function recurringCharges(): HasMany
    {
        return $this->hasMany(RecurringCharge::class, 'category_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function monthlyBudgets(): HasMany
    {
        return $this->hasMany(MonthlyBudget::class, 'category_id');
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
