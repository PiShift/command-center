<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordPaymentRequest;
use App\Mail\PaymentReceivedMailable;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Mail;

class InvoicePaymentController extends Controller
{
    public function __construct(private InvoiceService $service) {}

    public function store(RecordPaymentRequest $request, Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $data = $request->validated();
        if ($request->hasFile('proof')) {
            $data['proof'] = $request->file('proof');
        }

        $payment = $this->service->recordPayment($invoice, $data);

        if ($request->boolean('notify_customer') && $invoice->customer?->email) {
            $invoice->refresh();
            Mail::to($invoice->customer->email)
                ->queue(new PaymentReceivedMailable($invoice->load('customer'), $payment));
        }

        return back()->with('success', 'Payment recorded.');
    }
}
