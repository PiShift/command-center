<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceReminder;
use Illuminate\Http\Request;

class InvoiceReminderController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);

        $data = $request->validate([
            'scheduled_date' => [
                'required',
                'date',
                'after:today',
                function ($attribute, $value, $fail) use ($invoice) {
                    if ($invoice->due_date && $value <= $invoice->due_date->toDateString()) {
                        $fail('The reminder date must be after the invoice due date.');
                    }
                },
            ],
        ]);

        if ($invoice->reminders()->where('sent', false)->count() >= 2) {
            return back()->with('error', 'Maximum 2 pending reminders allowed per invoice.');
        }

        $invoice->reminders()->create([
            'scheduled_date' => $data['scheduled_date'],
        ]);

        return back()->with('success', 'Reminder scheduled.');
    }

    public function destroy(Invoice $invoice, InvoiceReminder $reminder)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        abort_unless($reminder->invoice_id === $invoice->id, 404);

        $reminder->delete();

        return back()->with('success', 'Reminder cancelled.');
    }
}
