<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyCreditRequest;
use App\Mail\CreditAppliedMailable;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Mail;

class CreditController extends Controller
{
    public function __construct(private InvoiceService $service) {}

    public function index(Customer $customer)
    {
        abort_unless(auth()->user()->hasPermission('invoices.view'), 403);
        $credits = CustomerCredit::where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->get();

        $balances = $credits->groupBy('currency')->map(
            fn($group, $currency) => CustomerCredit::getBalanceForCustomer($customer->id, $currency)
        );

        return view('credits.index', compact('customer', 'credits', 'balances'));
    }

    public function apply(ApplyCreditRequest $request, Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $creditAmount = (float) $request->input('amount');

        $this->service->applyCredit(
            $invoice,
            $request->integer('credit_id'),
            $creditAmount,
        );

        if ($request->boolean('notify_customer') && $invoice->customer?->email) {
            $invoice->refresh();
            Mail::to($invoice->customer->email)
                ->queue(new CreditAppliedMailable($invoice->load('customer'), $creditAmount));
        }

        return back()->with('success', 'Credit applied successfully.');
    }
}
