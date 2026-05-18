<x-layouts.app title="Edit {{ $invoice->invoice_number }}">

<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('invoices.show', $invoice) }}" style="color:#8c8c8a;text-decoration:none;font-size:13px" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">&larr; {{ $invoice->invoice_number }}</a>
        <h1 style="font-size:24px; font-weight:600; color:#141413">Edit Invoice</h1>
    </div>
</div>

@include('components.flash')

<form method="POST" action="{{ route('invoices.update', $invoice) }}">
    @csrf @method('PUT')
    @include('invoices.form', ['invoice' => $invoice, 'customers' => $customers, 'projects' => $projects])
    <div class="flex justify-end gap-3 mt-6">
        <a href="{{ route('invoices.show', $invoice) }}"
           style="display:inline-flex;align-items:center;padding:8px 16px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;text-decoration:none;transition:background 150ms ease"
           onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Cancel</a>
        <button type="submit"
                style="padding:8px 20px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border:none;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">Save Changes</button>
    </div>
</form>

</x-layouts.app>
