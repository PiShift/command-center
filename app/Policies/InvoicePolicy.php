<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool  { return true; }
    public function view(User $user, Invoice $invoice): bool { return true; }
    public function create(User $user): bool   { return true; }

    public function update(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'draft';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'draft';
    }

    public function publish(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'draft';
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'published' && $invoice->payment_status !== 'paid';
    }

    public function applyCredit(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'published' && $invoice->payment_status !== 'paid';
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return in_array($invoice->status, ['published', 'partially_paid', 'paid']);
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return !in_array($invoice->status, ['paid', 'cancelled']);
    }
}
