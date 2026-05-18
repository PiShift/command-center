<x-layouts.app title="Credits — {{ $customer->name }}">

<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('customers.show', $customer) }}" style="color:#8c8c8a;text-decoration:none;font-size:13px" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">&larr; {{ $customer->name }}</a>
        <h1 style="font-size:24px; font-weight:600; color:#141413">Credits</h1>
    </div>
</div>

@include('components.flash')

{{-- Balance summary --}}
@if($balances->isNotEmpty())
<div class="flex gap-4 mb-6">
    @foreach($balances as $currency => $balance)
    <div class="rounded-xl px-6 py-4 flex flex-col" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04);min-width:160px">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:4px">{{ $currency }} Balance</p>
        <p style="font-size:22px;font-weight:700;color:{{ $balance > 0 ? '#2e7d55' : '#141413' }}">{{ number_format($balance, 2) }}</p>
    </div>
    @endforeach
</div>
@endif

{{-- Credits table --}}
<div class="rounded-xl overflow-hidden" style="background:#fff; border:1px solid #e5e4df; box-shadow:0 1px 3px rgba(20,20,19,0.04)">
    @if($credits->isEmpty())
        <div class="py-16 text-center" style="color:#8c8c8a; font-size:13px">No credits for this customer.</div>
    @else
    <table class="w-full" style="font-size:13.5px">
        <thead>
            <tr style="background:#faf9f5; border-bottom:1px solid #e5e4df">
                <th class="px-6 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Description</th>
                <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Currency</th>
                <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Original</th>
                <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Remaining</th>
                <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Status</th>
                <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Created</th>
            </tr>
        </thead>
        <tbody>
            @php
                $creditStatusStyles = [
                    'available'      => 'background:#edf7f2; color:#2e7d55',
                    'partially_used' => 'background:#fef9ec; color:#9a7a1a',
                    'fully_used'     => 'background:#F5F4EF; color:#8c8c8a',
                ];
            @endphp
            @foreach($credits as $credit)
            <tr style="background:#fff;border-bottom:1px solid #eeeee9;transition:background 120ms ease"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='#fff'">
                <td class="px-6 py-3" style="color:#141413;font-weight:500">{{ $credit->description }}</td>
                <td class="px-4 py-3" style="color:#5c5c5a">{{ $credit->currency }}</td>
                <td class="px-4 py-3 text-right" style="color:#5c5c5a">{{ number_format($credit->amount_original, 2) }}</td>
                <td class="px-4 py-3 text-right" style="color:{{ $credit->amount_remaining > 0 ? '#2e7d55' : '#8c8c8a' }};font-weight:{{ $credit->amount_remaining > 0 ? '500' : '400' }}">{{ number_format($credit->amount_remaining, 2) }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold"
                          style="{{ $creditStatusStyles[$credit->status] ?? '' }}">
                        {{ str_replace('_', ' ', ucfirst($credit->status)) }}
                    </span>
                </td>
                <td class="px-4 py-3" style="color:#8c8c8a;font-size:12px">{{ $credit->created_at->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

</x-layouts.app>
