<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InvoicePayment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'invoice_id', 'customer_id', 'company_account_id', 'amount', 'currency',
        'payment_date', 'method', 'reference', 'notes',
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

    public function companyAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_account_id');
    }
}
