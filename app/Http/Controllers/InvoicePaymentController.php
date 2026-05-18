<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordPaymentRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;

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

        $this->service->recordPayment($invoice, $data);

        return back()->with('success', 'Payment recorded.');
    }
}
