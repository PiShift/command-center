<x-layouts.app title="{{ $invoice->invoice_number }}">

@php
    $statusStyles = [
        'draft'     => 'background:#F5F4EF; color:#8c8c8a',
        'published' => 'background:#eef3fb; color:#3a6fba',
        'cancelled' => 'background:#fdf0f0; color:#b94040',
    ];
    $paymentStyles = [
        'unpaid'        => 'background:#fff8ee; color:#9a5a1a',
        'partially_paid'=> 'background:#fef9ec; color:#9a7a1a',
        'paid'          => 'background:#edf7f2; color:#2e7d55',
    ];
@endphp

{{-- Page title row --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between mb-5 gap-3">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('invoices.index') }}" style="color:#8c8c8a;text-decoration:none;font-size:13px" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">&larr; Invoices</a>
        <h1 class="text-2xl md:text-4xl font-semibold" style="color:#141413">{{ $invoice->invoice_number }}</h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold"
              style="{{ $statusStyles[$invoice->status] ?? '' }}">
            {{ ucfirst($invoice->status) }}
        </span>
        @if($invoice->status === 'published')
        <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold"
              style="{{ $paymentStyles[$invoice->payment_status] ?? '' }}">
            {{ str_replace('_', ' ', ucfirst($invoice->payment_status)) }}
        </span>
        @endif
    </div>
    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
        @if($invoice->status === 'draft')
            <a href="{{ route('invoices.edit', $invoice) }}"
               class="flex sm:inline-flex w-full sm:w-auto justify-center items-center"
               style="padding:8px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;text-decoration:none;transition:background 150ms ease"
               onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Edit</a>
            {{-- Publish with notify_customer confirmation --}}
            <div x-data="{ open: false, notify: true }" class="w-full sm:w-auto">
                <button @click="open = true"
                        class="w-full sm:w-auto"
                        style="padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border:none;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                        onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">Publish</button>
                <div :style="open ? 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(20,20,19,0.4)' : 'display:none'"
                     @keydown.escape.window="open = false"
                     @click.self="open = false">
                    <div style="background:#fff;border-radius:12px;padding:24px;width:340px;border:1px solid #e5e4df;box-shadow:0 8px 32px rgba(20,20,19,0.12)">
                        <p style="font-size:15px;font-weight:600;color:#141413;margin-bottom:8px">Publish Invoice?</p>
                        <p style="font-size:13px;color:#5c5c5a;margin-bottom:16px">This will make the invoice visible and lock editing.</p>
                        @if($invoice->customer?->email)
                        <label class="flex items-center gap-2 mb-4" style="font-size:13px;color:#5c5c5a;cursor:pointer">
                            <input type="checkbox" x-model="notify" style="accent-color:#D97757">
                            Notify customer by email
                        </label>
                        @endif
                        <div class="flex gap-2 justify-end">
                            <button @click="open = false"
                                    style="padding:8px 16px;font-size:13px;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer">Cancel</button>
                            <form method="POST" action="{{ route('invoices.publish', $invoice) }}">
                                @csrf
                                <input type="hidden" name="notify_customer" :value="notify ? '1' : '0'">
                                <button type="submit"
                                        style="padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border:none;border-radius:8px;cursor:pointer">Publish</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if($invoice->status === 'published')
            <form method="POST" action="{{ route('invoices.reset-draft', $invoice) }}" class="contents" onsubmit="return confirm('Reset to draft? This will allow editing.')">@csrf @method('PATCH')
                <button type="submit"
                        class="flex sm:inline-flex w-full sm:w-auto items-center justify-center"
                        style="padding:8px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                        onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Reset to Draft</button>
            </form>
        @endif
        @if($invoice->status === 'published')
            <a href="{{ route('invoices.preview', $invoice) }}" target="_blank"
               class="flex sm:inline-flex w-full sm:w-auto justify-center items-center"
               style="padding:8px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;text-decoration:none;transition:background 150ms ease"
               onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Preview PDF</a>
            <a href="{{ route('invoices.download', $invoice) }}"
               class="flex sm:inline-flex w-full sm:w-auto justify-center items-center"
               style="padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;text-decoration:none;transition:background 150ms ease"
               onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">Download PDF</a>
        @endif
        @if(in_array($invoice->status, ['published', 'partially_paid', 'paid']) && $invoice->customer?->email)
            <form method="POST" action="{{ route('invoices.resend', $invoice) }}" class="contents" onsubmit="return confirm('Resend invoice to {{ $invoice->customer->email }}?')">
                @csrf
                <button type="submit"
                        class="flex sm:inline-flex w-full sm:w-auto items-center justify-center"
                        style="padding:8px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                        onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Resend to customer</button>
            </form>
        @endif
        @if($invoice->status !== 'cancelled' && $invoice->payment_status !== 'paid')
            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" class="contents" onsubmit="return confirm('Cancel this invoice?')">@csrf @method('PATCH')
                <button type="submit"
                        class="col-span-2 w-full sm:w-auto"
                        style="padding:8px 14px;font-size:13px;font-weight:500;background:#fff0f0;border:1px solid #ffd0d0;color:#b94040;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                        onmouseover="this.style.background='#ffe0e0'" onmouseout="this.style.background='#fff0f0'">Cancel</button>
            </form>
        @endif
    </div>
</div>

@include('components.flash')

@php
    $walletBalance = \App\Models\CustomerCredit::getBalanceForCustomer($invoice->customer_id, 'MRU');
@endphp
@if($walletBalance > 0 && $invoice->status !== 'cancelled' && $invoice->payment_status !== 'paid')
<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;margin-bottom:16px;background:#edf7f2;border:1px solid #b8e0cb;border-radius:10px">
    <div style="display:flex;align-items:center;gap:10px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2e7d55" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        <span style="font-size:13px;color:#2e7d55;font-weight:500">
            {{ $invoice->customer->name }} has <strong>MRU {{ number_format($walletBalance, 2) }}</strong> in wallet credit available
            @if($invoice->status === 'published')
                — use the <em>Apply Credit</em> panel on the right to apply it to this invoice.
            @else
                — publish this invoice first to apply it.
            @endif
        </span>
    </div>
    <a href="{{ route('credits.index', $invoice->customer) }}" style="font-size:12px;color:#2e7d55;text-decoration:none;white-space:nowrap;margin-left:16px" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View wallet →</a>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    {{-- Left: main content --}}
    <div class="col-span-1 md:col-span-2 flex flex-col gap-6">

        {{-- Meta card --}}
        <div class="rounded-xl p-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:4px">Customer</p>
                    <p style="font-weight:500;color:#141413">{{ $invoice->customer->name }}</p>
                    @if($invoice->customer->company)<p style="font-size:12px;color:#5c5c5a">{{ $invoice->customer->company }}</p>@endif
                </div>
                @if($invoice->project)
                <div>
                    <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:4px">Project</p>
                    <p style="font-weight:500;color:#141413">{{ $invoice->project->name }}</p>
                </div>
                @endif
                <div>
                    <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:4px">Issue Date</p>
                    <p style="color:#141413">{{ $invoice->issue_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:4px">Due Date</p>
                    <p style="color:{{ $invoice->is_overdue ? '#b94040' : '#141413' }};font-weight:{{ $invoice->is_overdue ? '500' : '400' }}">{{ $invoice->due_date->format('M d, Y') }}{{ $invoice->is_overdue ? ' · Overdue' : '' }}</p>
                </div>
                <div>
                    <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:4px">Currency</p>
                    <p style="color:#141413">{{ $invoice->currency }}</p>
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <div class="px-6 py-4" style="border-bottom:1px solid #eeeee9">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Line Items</p>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full" style="font-size:13px">
                <thead>
                    <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                        <th class="px-6 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;min-width:200px">Description</th>
                        <th class="px-4 py-3 text-center hidden sm:table-cell" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Unit</th>
                        <th class="px-4 py-3 text-right hidden sm:table-cell" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Qty</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;min-width:100px">Price</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;min-width:100px">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr style="border-bottom:1px solid #eeeee9">
                        <td class="px-6 py-3" style="color:#141413;line-height:1.6">
                            @include('invoices._item_description', ['description' => $item->description])
                            @if($item->cost_price > 0)
                            <span style="display:block;font-size:11px;color:#8c8c8a;margin-top:2px">cost: {{ $invoice->currency }} {{ number_format($item->cost_price, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center hidden sm:table-cell" style="color:#5c5c5a">{{ $item->unit }}</td>
                        <td class="px-4 py-3 text-right hidden sm:table-cell" style="color:#5c5c5a">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right" style="color:#5c5c5a">{{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-right" style="font-weight:500;color:#141413">{{ $invoice->currency }} {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            {{-- Totals --}}
            <div class="px-6 py-4" style="border-top:1px solid #eeeee9">
                <div class="flex flex-col items-end gap-1" style="font-size:13px">
                    <div class="flex gap-8"><span style="color:#8c8c8a">Subtotal</span><span style="color:#141413;font-weight:500">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span></div>
                    @if($invoice->discount_amount > 0)
                    <div class="flex gap-8"><span style="color:#8c8c8a">Discount</span><span style="color:#b94040">- {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</span></div>
                    @endif
                    @if($invoice->tax_amount > 0)
                    <div class="flex gap-8"><span style="color:#8c8c8a">Tax ({{ $invoice->tax_rate }}%)</span><span style="color:#141413">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</span></div>
                    @endif
                    <div class="flex gap-8 mt-1 pt-2" style="border-top:1px solid #eeeee9"><span style="font-weight:700;color:#141413;font-size:15px">Total</span><span style="font-weight:700;color:#141413;font-size:15px">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span></div>
                    @if($invoice->amount_paid > 0)
                    <div class="flex gap-8"><span style="color:#2e7d55">Paid</span><span style="color:#2e7d55">{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</span></div>
                    @endif
                    <div class="flex gap-8"><span style="font-weight:600;color:{{ $invoice->amount_due > 0 ? '#b94040' : '#2e7d55' }}">Amount Due</span><span style="font-weight:600;color:{{ $invoice->amount_due > 0 ? '#b94040' : '#2e7d55' }}">{{ $invoice->currency }} {{ number_format($invoice->amount_due, 2) }}</span></div>
                </div>
            </div>

            @php
                $totalCost = $invoice->items->sum(fn($i) => (float) $i->cost_price);
                $revenue   = (float) $invoice->total;
                $margin    = $revenue - $totalCost;
                $marginPct = $revenue > 0 ? ($margin / $revenue * 100) : 0;
                $autoExpenseCount = \App\Models\Expense::whereIn('source_invoice_item_id', $invoice->items->pluck('id'))->count();
            @endphp
            @if($totalCost > 0)
            {{-- Margin block --}}
            <div class="px-6 py-4" style="border-top:1px solid #eeeee9;background:#faf9f5">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:10px">Margin</p>
                <div class="flex flex-col items-end gap-1" style="font-size:13px">
                    <div class="flex gap-8"><span style="color:#8c8c8a">Revenue</span><span style="color:#141413;font-weight:500">{{ $invoice->currency }} {{ number_format($revenue, 2) }}</span></div>
                    <div class="flex gap-8"><span style="color:#8c8c8a">Cost</span><span style="color:#141413;font-weight:500">{{ $invoice->currency }} {{ number_format($totalCost, 2) }}</span></div>
                    <div class="flex gap-8 mt-1 pt-2" style="border-top:1px solid #eeeee9">
                        <span style="font-weight:600;color:#141413">Margin</span>
                        <span style="font-weight:700;color:{{ $margin >= 0 ? '#2e7d55' : '#b94040' }}">
                            {{ $margin < 0 ? '−' : '' }}{{ $invoice->currency }} {{ number_format(abs($margin), 2) }}
                            <span style="font-size:11px;font-weight:500;margin-left:4px">({{ number_format($marginPct, 1) }}%)</span>
                        </span>
                    </div>
                </div>
                @if($autoExpenseCount > 0)
                <div class="mt-3 text-right">
                    <a href="/expenses?source_invoice={{ $invoice->id }}"
                       style="font-size:12px;color:#8c8c8a;text-decoration:none"
                       onmouseover="this.style.color='#5c5c5a'"
                       onmouseout="this.style.color='#8c8c8a'"
                    >View related expenses ({{ $autoExpenseCount }}) →</a>
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Payments --}}
        @if($invoice->payments->isNotEmpty())
        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <div class="px-6 py-4" style="border-bottom:1px solid #eeeee9">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Payments</p>
            </div>
            <table class="w-full" style="font-size:13px">
                <thead>
                    <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                        <th class="px-6 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Date</th>
                        <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Method</th>
                        <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Reference</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                    <tr style="border-bottom:1px solid #eeeee9">
                        <td class="px-6 py-3" style="color:#5c5c5a;font-size:12px">{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3" style="color:#5c5c5a">{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td>
                        <td class="px-4 py-3" style="color:#8c8c8a;font-size:12px">{{ $payment->reference ?? '—' }}</td>
                        <td class="px-4 py-3 text-right" style="font-weight:500;color:#2e7d55">{{ $invoice->currency }} {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Credit allocations --}}
        @if($invoice->creditAllocations->isNotEmpty())
        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <div class="px-6 py-4" style="border-bottom:1px solid #eeeee9">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Credit Allocations</p>
            </div>
            <table class="w-full" style="font-size:13px">
                <tbody>
                    @foreach($invoice->creditAllocations as $alloc)
                    <tr style="border-bottom:1px solid #eeeee9">
                        <td class="px-6 py-3" style="color:#5c5c5a">{{ $alloc->credit->description }}</td>
                        <td class="px-4 py-3" style="color:#8c8c8a;font-size:12px">{{ $alloc->allocated_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right" style="font-weight:500;color:#2e7d55">{{ $invoice->currency }} {{ number_format($alloc->amount_applied, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Right sidebar --}}
    <div class="flex flex-col gap-5">

        {{-- Record payment --}}
        @if($invoice->status === 'published' && $invoice->payment_status !== 'paid')
        <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <p style="font-size:13px;font-weight:600;color:#141413;margin-bottom:14px">Record Payment</p>
            <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                @csrf
                @foreach([
                    ['name'=>'amount','label'=>'Amount','type'=>'number','attrs'=>'step="0.01" min="0.01"'],
                    ['name'=>'payment_date','label'=>'Date','type'=>'date','attrs'=>''],
                    ['name'=>'reference','label'=>'Reference (optional)','type'=>'text','attrs'=>''],
                ] as $field)
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">{{ $field['label'] }}</label>
                    <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" {{ $field['attrs'] }}
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                </div>
                @endforeach
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Method</label>
                    <div class="relative">
                        <select name="method" class="w-full appearance-none rounded-lg text-[13px] pl-3 pr-8 py-2"
                                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
                            @foreach(['bank_transfer'=>'Bank Transfer','cash'=>'Cash','check'=>'Check','card'=>'Card','other'=>'Other'] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Proof (optional)</label>
                    <x-file-upload name="proof" accept=".jpg,.jpeg,.png,.pdf" label="Drop payment proof here" />
                </div>
                @if($invoice->customer?->email)
                <label class="flex items-center gap-2" style="font-size:13px;color:#5c5c5a;cursor:pointer">
                    <input type="checkbox" name="notify_customer" value="1" checked style="accent-color:#D97757">
                    Notify customer by email
                </label>
                @endif
                <button type="submit"
                        style="padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border:none;border-radius:8px;cursor:pointer;transition:background 150ms ease;width:100%"
                        onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">Record Payment</button>
            </form>
        </div>
        @endif

        {{-- Apply credit --}}
        @if($invoice->status === 'published' && $invoice->payment_status !== 'paid' && $credits->isNotEmpty())
        <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <p style="font-size:13px;font-weight:600;color:#141413;margin-bottom:14px">Apply Credit</p>
            @if($errors->hasAny(['amount','credit_id','credit','invoice']))
            <div style="padding:10px 12px;margin-bottom:12px;background:#fff8f8;border:1px solid #ffd0d0;border-radius:8px;font-size:12px;color:#b94040">
                @foreach(['amount','credit_id','credit','invoice'] as $field)
                    @error($field)<p>{{ $message }}</p>@enderror
                @endforeach
            </div>
            @endif
            <form method="POST" action="{{ route('invoices.apply-credit', $invoice) }}" class="flex flex-col gap-3"
                  x-data="{ selectedCredit: {{ $credits->first()?->id ?? 'null' }}, credits: {{ Js::from($credits->map(fn($c) => ['id' => $c->id, 'remaining' => $c->amount_remaining])) }} }">
                @csrf
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Credit</label>
                    <div class="relative">
                        <select name="credit_id" class="w-full appearance-none rounded-lg text-[13px] pl-3 pr-8 py-2"
                                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                                x-model="selectedCredit">
                            @foreach($credits as $credit)
                            <option value="{{ $credit->id }}">{{ $credit->description }} (MRU {{ number_format($credit->amount_remaining, 2) }} remaining)</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Amount</label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                           :max="Math.min(credits.find(c => String(c.id) === String(selectedCredit))?.remaining ?? 0, {{ $invoice->amount_due }})"
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'"
                           value="{{ old('amount') }}">
                    <p style="font-size:11px;color:#8c8c8a;margin-top:3px">
                        Max: MRU <span x-text="Math.min(credits.find(c => String(c.id) === String(selectedCredit))?.remaining ?? 0, {{ $invoice->amount_due }}).toFixed(2)"></span>
                    </p>
                </div>
                @if($invoice->customer?->email)
                <label class="flex items-center gap-2" style="font-size:13px;color:#5c5c5a;cursor:pointer">
                    <input type="checkbox" name="notify_customer" value="1" style="accent-color:#D97757">
                    Notify customer by email
                </label>
                @endif
                <button type="submit"
                        style="padding:8px 16px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer;transition:background 150ms ease;width:100%"
                        onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Apply Credit</button>
            </form>
        </div>
        @endif

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:8px">Payment Info / Notes</p>
            <p style="font-size:13px;color:#5c5c5a;white-space:pre-wrap;line-height:1.5">{{ $invoice->notes }}</p>
        </div>
        @endif

        {{-- Scheduled Reminders --}}
        @if($invoice->status === 'published' && $invoice->payment_status !== 'paid')
        <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <p style="font-size:13px;font-weight:600;color:#141413;margin-bottom:12px">Scheduled Reminders</p>

            @if($invoice->reminders->isNotEmpty())
            <div class="flex flex-col gap-2 mb-4">
                @foreach($invoice->reminders as $reminder)
                <div class="flex items-center justify-between rounded-lg px-3 py-2" style="background:#F5F4EF;border:1px solid #e5e4df">
                    <div>
                        <span style="font-size:13px;color:#141413;font-weight:500">{{ $reminder->scheduled_date->format('M d, Y') }}</span>
                        @if($reminder->sent)
                            <span style="margin-left:8px;font-size:11px;color:#2e7d55;background:#eafaf1;border:1px solid #b7eacf;padding:1px 7px;border-radius:20px">Sent</span>
                        @else
                            <span style="margin-left:8px;font-size:11px;color:#5c5c5a;background:#F5F4EF;border:1px solid #e5e4df;padding:1px 7px;border-radius:20px">Pending</span>
                        @endif
                    </div>
                    @if(! $reminder->sent)
                    <form method="POST" action="{{ route('invoices.reminders.destroy', [$invoice, $reminder]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size:12px;color:#b94040;background:none;border:none;cursor:pointer;padding:0"
                                onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Cancel</button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            @if($invoice->reminders->where('sent', false)->count() < 2)
            <form method="POST" action="{{ route('invoices.reminders.store', $invoice) }}" class="flex gap-2 items-center">
                @csrf
                <div class="flex-1">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Reminder Date</label>
                    <input type="date" name="scheduled_date"
                           min="{{ now()->addDay()->toDateString() }}"
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'"
                           value="{{ old('scheduled_date') }}">
                    @error('scheduled_date')<p style="font-size:11px;color:#b94040;margin-top:3px">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                        class="shrink-0"
                        style="padding:8px 16px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer;transition:background 150ms ease;white-space:nowrap"
                        onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Schedule Reminder</button>
            </form>
            @else
            <p style="font-size:12px;color:#8c8c8a">Maximum 2 pending reminders reached.</p>
            @endif
        </div>
        @endif

    </div>
</div>

</x-layouts.app>
