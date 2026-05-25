<x-layouts.app title="Payments">

@php
    $sortLink = fn(string $col) => request()->fullUrlWithQuery([
        'sort'      => $col,
        'direction' => ($sort === $col && $direction === 'desc') ? 'asc' : 'desc',
        'page'      => 1,
    ]);
    $methodLabels = [
        'bank_transfer' => 'Bank Transfer',
        'cash'          => 'Cash',
        'check'         => 'Check',
        'card'          => 'Card',
        'other'         => 'Other',
    ];
    $methodStyles = [
        'bank_transfer' => 'background:#eef3fb;color:#3a6fba',
        'cash'          => 'background:#edf7f2;color:#2e7d55',
        'check'         => 'background:#F5F4EF;color:#5c5c5a',
        'card'          => 'background:#f3eefb;color:#6a3ab0',
        'other'         => 'background:#fef9ec;color:#9a7a1a',
    ];
@endphp

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 style="font-size:24px;font-weight:600;color:#141413">Payments</h1>
        <p style="font-size:13px;color:#8c8c8a;margin-top:2px">
            All recorded payments across invoices
        </p>
    </div>
    <div style="text-align:right">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a;margin-bottom:2px">Total Collected</div>
        <div style="font-size:22px;font-weight:700;color:#141413">
            {{ number_format($totalAmount, 2) }}
        </div>
    </div>
</div>

@include('components.flash')

{{-- ── Filters ──────────────────────────────────────────────────────────────── --}}
<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="direction" value="{{ $direction }}">

    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search payments…"
           class="text-[13px] pl-3 pr-3 py-2 rounded-lg"
           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none;width:220px">

    <div class="relative">
        <select name="method" onchange="this.form.submit()"
                class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer"
                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
            <option value="">All Methods</option>
            @foreach($methodLabels as $val => $lab)
                <option value="{{ $val }}" {{ request('method') == $val ? 'selected' : '' }}>{{ $lab }}</option>
            @endforeach
        </select>
        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </div>

    @if(request()->hasAny(['search','method']))
        <a href="{{ route('payments.index') }}" style="display:flex;align-items:center;padding:8px 12px;font-size:13px;color:#8c8c8a;text-decoration:none"
           onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">✕ Clear</a>
    @endif

    <button type="submit" style="padding:8px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;cursor:pointer;color:#141413">
        Search
    </button>
</form>

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
<div class="rounded-xl" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,.04)">
    @if($payments->isEmpty())
        <div class="py-16 text-center" style="color:#8c8c8a;font-size:13px">No payments found.</div>
    @else
    <div class="overflow-x-auto">
    <table class="w-full" style="font-size:13.5px;min-width:680px">
        <thead>
            <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                @php $cols = [
                    ['col' => 'payment_date', 'label' => 'Date',     'cls' => 'px-6 py-3 text-left'],
                    ['col' => null,           'label' => 'Invoice',  'cls' => 'px-4 py-3 text-left'],
                    ['col' => null,           'label' => 'Customer', 'cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                    ['col' => 'method',       'label' => 'Method',   'cls' => 'px-4 py-3 text-left'],
                    ['col' => null,           'label' => 'Reference','cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                    ['col' => 'amount',       'label' => 'Amount',   'cls' => 'px-4 py-3 text-right'],
                    ['col' => null,           'label' => 'Proof',    'cls' => 'px-4 py-3 text-center hidden sm:table-cell'],
                ]; @endphp
                @foreach($cols as $th)
                <th class="{{ $th['cls'] }}" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a;white-space:nowrap">
                    @if($th['col'])
                        <a href="{{ $sortLink($th['col']) }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            {{ $th['label'] }}
                            <span style="color:{{ $sort===$th['col']?'#D97757':'#d8d7d2' }}">{!! $sort===$th['col']?($direction==='asc'?'↑':'↓'):'↕' !!}</span>
                        </a>
                    @else
                        {{ $th['label'] }}
                    @endif
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr style="background:#fff;border-bottom:1px solid #eeeee9;transition:background 100ms ease"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='#fff'">

                {{-- Date --}}
                <td class="px-6 py-3" style="color:#141413;font-weight:500;white-space:nowrap">
                    {{ $payment->payment_date->format('M d, Y') }}
                </td>

                {{-- Invoice --}}
                <td class="px-4 py-3">
                    @if($payment->invoice)
                        <a href="{{ route('invoices.show', $payment->invoice) }}"
                           style="font-weight:500;color:#141413;text-decoration:none"
                           onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">
                            {{ $payment->invoice->invoice_number }}
                        </a>
                    @else
                        <span style="color:#8c8c8a">—</span>
                    @endif
                </td>

                {{-- Customer --}}
                <td class="px-4 py-3 hidden sm:table-cell" style="color:#5c5c5a">
                    {{ $payment->customer?->name ?? $payment->invoice?->customer?->name ?? '—' }}
                </td>

                {{-- Method --}}
                <td class="px-4 py-3">
                    <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;{{ $methodStyles[$payment->method] ?? 'background:#F5F4EF;color:#5c5c5a' }}">
                        {{ $methodLabels[$payment->method] ?? ucfirst($payment->method) }}
                    </span>
                </td>

                {{-- Reference --}}
                <td class="px-4 py-3 hidden sm:table-cell" style="color:#5c5c5a;font-size:12px">
                    {{ $payment->reference ?: '—' }}
                </td>

                {{-- Amount --}}
                <td class="px-4 py-3 text-right" style="font-weight:600;color:#141413">
                    {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
                </td>

                {{-- Proof --}}
                <td class="px-4 py-3 text-center hidden sm:table-cell">
                    @if($payment->getFirstMediaUrl('proof'))
                        <a href="{{ $payment->getFirstMediaUrl('proof') }}" target="_blank"
                           title="View proof" style="color:#3a6fba;text-decoration:none;font-size:12px"
                           onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#3a6fba'">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block;vertical-align:middle"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </a>
                    @else
                        <span style="color:#d8d7d2">—</span>
                    @endif
                </td>
            </tr>
            @if($payment->notes)
            <tr style="background:#faf9f5;border-bottom:1px solid #eeeee9">
                <td></td>
                <td colspan="6" class="px-4 py-2" style="font-size:12px;color:#8c8c8a;font-style:italic">
                    {{ $payment->notes }}
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="mt-4 px-4 pb-4 flex items-center justify-between" style="border-top:1px solid #eeeee9">
        <div style="font-size:13px;color:#8c8c8a">
            {{ $payments->total() }} payment{{ $payments->total() !== 1 ? 's' : '' }}
        </div>
        {{ $payments->links() }}
    </div>
    @endif
</div>

</x-layouts.app>
