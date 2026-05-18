<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditAllocation extends Model
{
    protected $fillable = [
        'credit_id', 'invoice_id', 'customer_id',
        'amount_applied', 'allocated_at', 'notes',
    ];

    protected $casts = [
        'amount_applied' => 'float',
        'allocated_at'   => 'datetime',
    ];

    public function credit(): BelongsTo
    {
        return $this->belongsTo(CustomerCredit::class, 'credit_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
