<x-layouts.app title="Employee Loan Details">

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 style="font-size:24px;font-weight:600;color:#141413">Loan Details</h1>
        <p style="font-size:13px;color:#8c8c8a;margin-top:2px">
            {{ $employee->display_name }} · {{ $loan->title }}
        </p>
    </div>
    <a href="{{ route('employees.show', $employee) }}#loans"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;color:#141413;text-decoration:none">
        Back to Employee
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:6px">Amount Total</p>
        <p style="font-size:24px;font-weight:700;color:#141413">{{ number_format((float) $loan->amount_total, 2) }}</p>
    </div>

    <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:6px">Amount Repaid</p>
        <p style="font-size:24px;font-weight:700;color:#2e7d55">{{ number_format($loan->amount_repaid, 2) }}</p>
    </div>

    <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:6px">Remaining Balance</p>
        <p style="font-size:24px;font-weight:700;color:{{ $loan->amount_remaining > 0 ? '#b94040' : '#2e7d55' }}">
            {{ number_format($loan->amount_remaining, 2) }}
        </p>
    </div>
</div>

<div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
    <div class="px-6 py-4" style="border-bottom:1px solid #eeeee9">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Repayment History</p>
        <p style="font-size:13px;color:#5c5c5a;margin-top:6px">
            Progress: {{ $loan->progress_percentage }}% · {{ $loan->repayments->count() }} repayment{{ $loan->repayments->count() !== 1 ? 's' : '' }}
        </p>
    </div>

    @if($loan->repayments->isEmpty())
        <div class="py-12 text-center" style="font-size:13px;color:#8c8c8a">No repayments recorded yet.</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full" style="font-size:13px;min-width:640px">
                <thead>
                    <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                        <th class="px-6 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Date</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Amount</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Salary Snapshot</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">% Snapshot</th>
                        <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loan->repayments as $repayment)
                        <tr style="border-bottom:1px solid #eeeee9">
                            <td class="px-6 py-3" style="color:#5c5c5a">{{ $repayment->repayment_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right" style="font-weight:500;color:#2e7d55">{{ number_format((float) $repayment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-right" style="color:#5c5c5a">{{ number_format((float) $repayment->salary_snapshot, 2) }}</td>
                            <td class="px-4 py-3 text-right" style="color:#5c5c5a">{{ $repayment->percentage_snapshot !== null ? number_format((float) $repayment->percentage_snapshot, 2) . '%' : '—' }}</td>
                            <td class="px-4 py-3" style="color:#8c8c8a">{{ $repayment->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

</x-layouts.app>
