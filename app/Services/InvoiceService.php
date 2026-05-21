<?php

namespace App\Services;

use App\Models\CreditAllocation;
use App\Models\CustomerCredit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    // ── Calculate totals ──────────────────────────────────────────────────────
    public function calculateTotals(Invoice $invoice): void
    {
        $invoice->load('items');

        $subtotal = $invoice->items->sum('subtotal');

        $discount = 0;
        if ($invoice->discount_type === 'percent' && $invoice->discount_value) {
            $discount = $subtotal * $invoice->discount_value / 100;
        } elseif ($invoice->discount_type === 'fixed' && $invoice->discount_value) {
            $discount = (float) $invoice->discount_value;
        }

        $taxable   = $subtotal - $discount;
        $taxAmount = $invoice->tax_rate ? ($taxable * $invoice->tax_rate / 100) : 0;
        $total     = $taxable + $taxAmount;

        $invoice->subtotal        = $subtotal;
        $invoice->discount_amount = $discount;
        $invoice->tax_amount      = $taxAmount;
        $invoice->total           = max(0, $total);
        $invoice->save();
    }

    // ── Publish ───────────────────────────────────────────────────────────────
    public function publish(Invoice $invoice): void
    {
        if ($invoice->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Cannot publish an invoice with no items.']);
        }
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft invoices can be published.']);
        }

        $invoice->status = 'published';
        $invoice->save();
    }

    // ── Record payment ────────────────────────────────────────────────────────
    public function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data) {
        if (in_array($invoice->status, ['draft', 'cancelled'])) {
                throw ValidationException::withMessages(['invoice' => 'Cannot record payment on a draft or cancelled invoice.']);
            }

            // Handle proof upload
            if (!empty($data['proof'])) {
                $proofFile = $data['proof'];
            }
            unset($data['proof']);

            $payment = InvoicePayment::create(array_merge($data, [
                'invoice_id'  => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'currency'    => $invoice->currency,
            ]));

            if (!empty($proofFile)) {
                $payment->addMedia($proofFile)->toMediaCollection('proof');
            }

            Cache::tags(['dashboard'])->flush();

            return $payment;
        });
    }

    // ── Sync invoice status after payment ────────────────────────────────────
    public function syncInvoiceAfterPayment(Invoice $invoice, InvoicePayment $payment): void
    {
        DB::transaction(function () use ($invoice, $payment) {
            $invoice = $invoice->fresh();

            $totalPaid = InvoicePayment::where('invoice_id', $invoice->id)->sum('amount');
            $invoice->amount_paid = $totalPaid;

            if ($totalPaid >= $invoice->total) {
                $invoice->payment_status = 'paid';

                $overpayment = $totalPaid - $invoice->total;
                if ($overpayment > 0.001) {
                    CustomerCredit::create([
                        'customer_id'      => $invoice->customer_id,
                        'source_type'      => 'overpayment',
                        'source_id'        => $payment->id,
                        'currency'         => $invoice->currency,
                        'amount_original'  => $overpayment,
                        'amount_remaining' => $overpayment,
                        'status'           => 'available',
                        'description'      => 'Overpayment on ' . $invoice->invoice_number,
                    ]);
                }
            } elseif ($totalPaid > 0) {
                $invoice->payment_status = 'partially_paid';
            } else {
                $invoice->payment_status = 'unpaid';
            }

            $invoice->save();
        });
    }

    // ── Apply credit ──────────────────────────────────────────────────────────
    public function applyCredit(Invoice $invoice, int $creditId, float $amount): CreditAllocation
    {
        return DB::transaction(function () use ($invoice, $creditId, $amount) {
            if (in_array($invoice->status, ['draft', 'cancelled'])) {
                throw ValidationException::withMessages(['invoice' => 'Cannot apply credit to a draft or cancelled invoice.']);
            }

            $credit = CustomerCredit::findOrFail($creditId);

            if ($credit->customer_id !== $invoice->customer_id) {
                throw ValidationException::withMessages(['credit' => 'Credit does not belong to this customer.']);
            }
            if ($credit->amount_remaining < $amount) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds available credit balance.']);
            }
            if ($amount > $invoice->amount_due) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds invoice amount due.']);
            }

            $allocation = CreditAllocation::create([
                'credit_id'      => $credit->id,
                'invoice_id'     => $invoice->id,
                'customer_id'    => $invoice->customer_id,
                'amount_applied' => $amount,
                'allocated_at'   => now(),
            ]);

            $credit->amount_remaining -= $amount;
            $credit->status = $credit->amount_remaining <= 0
                ? 'fully_used'
                : 'partially_used';
            $credit->save();

            $invoice->amount_paid += $amount;
            if ($invoice->amount_paid >= $invoice->total) {
                $invoice->payment_status = 'paid';
            } elseif ($invoice->amount_paid > 0) {
                $invoice->payment_status = 'partially_paid';
            }
            $invoice->save();

            return $allocation;
        });
    }

    // ── Generate PDF ──────────────────────────────────────────────────────────
    public function generatePdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['customer', 'project', 'items.task', 'payments']);

        // Embed wide logo as base64 PNG (already PNG, no conversion needed)
        $logoPath = null;
        $logoFile = public_path('images/logo.png');
        if (file_exists($logoFile)) {
            $logoPath = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', [
            'invoice'  => $invoice,
            'logoPath' => $logoPath,
        ])->setPaper('a4', 'portrait');
    }
}
