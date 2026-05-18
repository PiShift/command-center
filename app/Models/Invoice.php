<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'customer_id', 'project_id',
        'issue_date', 'due_date', 'currency', 'exchange_rate',
        'subtotal', 'discount_type', 'discount_value', 'discount_amount',
        'tax_rate', 'tax_amount', 'total', 'amount_paid',
        'status', 'notes',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'due_date'    => 'date',
        'subtotal'    => 'float',
        'discount_amount' => 'float',
        'tax_amount'  => 'float',
        'total'       => 'float',
        'amount_paid' => 'float',
    ];

    // ── Auto-generate invoice_number ─────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $year  = now()->year;
            $count = static::whereYear('created_at', $year)->withTrashed()->count() + 1;
            $invoice->invoice_number = 'INV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    // ── Relationships ────────────────────────────────────────────────────────
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date');
    }

    public function creditAllocations(): HasMany
    {
        return $this->hasMany(CreditAllocation::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeDraft($q)          { return $q->where('status', 'draft'); }
    public function scopePublished($q)      { return $q->where('status', 'published'); }
    public function scopeUnpaid($q)         { return $q->whereIn('status', ['published', 'partially_paid']); }
    public function scopeOverdue($q)
    {
        return $q->whereNotIn('status', ['paid', 'cancelled'])
                 ->where('due_date', '<', now()->toDateString());
    }

    // ── Accessors ────────────────────────────────────────────────────────────
    public function getAmountDueAttribute(): float
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date < Carbon::today()
            && !in_array($this->status, ['paid', 'cancelled']);
    }
}
