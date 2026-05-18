<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id', 'customer_id', 'amount', 'currency',
        'payment_date', 'method', 'reference', 'proof_path', 'notes',
    ];

    protected $casts = [
        'amount'       => 'float',
        'payment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (InvoicePayment $payment) {
            app(\App\Services\InvoiceService::class)
                ->syncInvoiceAfterPayment($payment->invoice, $payment);
        });
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
