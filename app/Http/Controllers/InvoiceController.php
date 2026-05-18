<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('invoices.view'), 403);
        $sortable  = ['invoice_number', 'issue_date', 'due_date', 'total', 'status'];
        $sort      = in_array($request->sort, $sortable) ? $request->sort : 'issue_date';
        $direction = $request->direction === 'asc' ? 'asc' : 'desc';

        $query = Invoice::with(['customer', 'project'])->orderBy($sort, $direction);

        if ($s = $request->search) {
            $query->where(fn($q) => $q->where('invoice_number', 'like', "%$s%")
                ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%$s%")));
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($request->overdue) {
            $query->overdue();
        }

        $invoices  = $query->paginate(25)->withQueryString();
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('invoices.index', compact('invoices', 'customers', 'sort', 'direction'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $projects  = Project::orderBy('name')->get(['id', 'name', 'customer_id']);
        return view('invoices.create', compact('customers', 'projects'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $data = array_merge(['currency' => 'MRU'], $request->validated());
        $invoice = Invoice::create($data);

        // Persist line items
        $this->syncItems($invoice, $request);
        $this->service->calculateTotals($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.view'), 403);
        $invoice->load(['customer', 'project', 'items.task', 'payments', 'creditAllocations.credit']);
        $credits = \App\Models\CustomerCredit::where('customer_id', $invoice->customer_id)
            ->where('currency', $invoice->currency)
            ->available()
            ->get();

        return view('invoices.show', compact('invoice', 'credits'));
    }

    public function edit(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $invoice->load('items.task');
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $projects  = Project::orderBy('name')->get(['id', 'name', 'customer_id']);
        return view('invoices.edit', compact('invoice', 'customers', 'projects'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $invoice->update($request->validated());
        $this->syncItems($invoice, $request);
        $this->service->calculateTotals($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function publish(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $this->service->publish($invoice);
        return back()->with('success', 'Invoice published.');
    }

    public function cancel(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Invoice cancelled.');
    }

    public function resetToDraft(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $invoice->update(['status' => 'draft']);
        return back()->with('success', 'Invoice reset to draft.');
    }

    public function bulkAction(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);
        $ids    = array_filter(explode(',', (string) $request->input('ids', '')));
        $action = $request->input('action');

        if (empty($ids)) {
            return back()->with('error', 'No invoices selected.');
        }

        $invoices = Invoice::whereIn('id', $ids)->get();

        match ($action) {
            'delete'         => $invoices->each->delete(),
            'cancel'         => $invoices->each(fn($i) => $i->update(['status' => 'cancelled'])),
            'reset_to_draft' => $invoices->each(fn($i) => $i->update(['status' => 'draft'])),
            default          => null,
        };

        return back()->with('success', count($invoices) . ' invoice(s) updated.');
    }

    public function download(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.view'), 403);
        $pdf = $this->service->generatePdf($invoice);
        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function preview(Invoice $invoice)
    {
        abort_unless(auth()->user()->hasPermission('invoices.view'), 403);
        $pdf = $this->service->generatePdf($invoice);
        return $pdf->stream($invoice->invoice_number . '.pdf');
    }

    // ── Private helpers ───────────────────────────────────────────────────────
    private function syncItems(Invoice $invoice, Request $request): void
    {
        $invoice->items()->delete();
        $rows = $request->input('items', []);
        foreach ($rows as $i => $row) {
            if (empty($row['description']) && empty($row['unit_price'])) continue;
            InvoiceItem::create([
                'invoice_id'     => $invoice->id,
                'type'           => $row['type'] ?? 'manual',
                'task_id'        => $row['task_id'] ?? null,
                'description'    => $row['description'],
                'quantity'       => $row['quantity'] ?? 1,
                'unit'           => $row['unit'] ?? 'units',
                'unit_price'     => $row['unit_price'] ?? 0,
                'discount_type'  => $row['discount_type'] ?? null,
                'discount_value' => $row['discount_value'] ?? null,
                'sort_order'     => $i,
            ]);
        }
    }
}
