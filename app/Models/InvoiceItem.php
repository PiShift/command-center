<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'type', 'task_id', 'description',
        'quantity', 'unit', 'unit_price',
        'discount_type', 'discount_value', 'subtotal', 'sort_order',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'unit_price'     => 'float',
        'discount_value' => 'float',
        'subtotal'       => 'float',
    ];

    // ── Auto-compute subtotal on save ────────────────────────────────────────
    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $base = $item->quantity * $item->unit_price;

            if ($item->discount_type === 'percent' && $item->discount_value) {
                $item->subtotal = $base - ($base * $item->discount_value / 100);
            } elseif ($item->discount_type === 'fixed' && $item->discount_value) {
                $item->subtotal = $base - $item->discount_value;
            } else {
                $item->subtotal = $base;
            }

            $item->subtotal = max(0, $item->subtotal);
        });
    }

    // ── Relationships ────────────────────────────────────────────────────────
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    // ── Factory from Task ────────────────────────────────────────────────────
    public static function fromTask(Task $task): self
    {
        return new self([
            'type'        => 'task',
            'task_id'     => $task->id,
            'description' => $task->title,
            'quantity'    => $task->estimated_hours ?? 1,
            'unit'        => 'hours',
            'unit_price'  => 0,
        ]);
    }
}
