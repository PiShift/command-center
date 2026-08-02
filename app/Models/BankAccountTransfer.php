<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccountTransfer extends Model
{
    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'amount',
        'amount_sent',
        'amount_received',
        'exchange_rate',
        'date',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_sent' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'date' => 'date',
    ];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'to_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
