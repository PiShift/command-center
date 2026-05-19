<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReminder extends Model
{
    protected $fillable = ['invoice_id', 'scheduled_date', 'sent', 'sent_at'];

    protected $casts = [
        'scheduled_date' => 'date',
        'sent'           => 'boolean',
        'sent_at'        => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
