<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerCredit extends Model
{
    protected $fillable = [
        'customer_id', 'source_type', 'source_id', 'currency',
        'amount_original', 'amount_remaining', 'status', 'description',
    ];

    protected $casts = [
        'amount_original'  => 'float',
        'amount_remaining' => 'float',
    ];

    // ── Relationships ────────────────────────────────────────────────────────
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CreditAllocation::class, 'credit_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeAvailable($q)
    {
        return $q->where('status', '!=', 'fully_used')->where('amount_remaining', '>', 0);
    }

    // ── Static helper ─────────────────────────────────────────────────────────
    public static function getBalanceForCustomer(int $customerId, string $currency): float
    {
        return (float) static::where('customer_id', $customerId)
            ->where('currency', $currency)
            ->available()
            ->sum('amount_remaining');
    }
}
