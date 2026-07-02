<x-layouts.app title="Payroll Runs">
    @php
        $fmt = fn($n) => 'MRU ' . number_format((float) $n, 2);
    @endphp

    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Payroll Runs</h1>
            <p class="mt-1 text-[13px] text-muted">Monthly payroll history and current run status.</p>
        </div>
        <a href="{{ route('payroll.create') }}" class="inline-flex items-center rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white hover:bg-accent-hover transition-colors">
            + Run Payroll
        </a>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-line bg-white p-4 shadow-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Last Paid Payroll</p>
            <p class="mt-2 text-lg font-semibold text-ink">{{ $lastPaidRun?->month?->format('F Y') ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-line bg-white p-4 shadow-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Total Paid This Year</p>
            <p class="mt-2 text-lg font-semibold text-success-text">{{ $fmt($totalPaidThisYear) }}</p>
        </div>
        <div class="rounded-xl border border-line bg-white p-4 shadow-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Next Payroll Due</p>
            <p class="mt-2 text-lg font-semibold text-ink">{{ $nextPayrollDue->format('M d, Y') }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-white shadow-card">
        <table class="w-full min-w-[920px] border-collapse">
            <thead>
                <tr class="border-b border-line bg-canvas">
                    <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Month</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Status</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Employees</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Gross Total</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Net Total</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Paid Date</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $run)
                    <tr class="border-b border-hairline last:border-b-0 hover:bg-canvas transition-colors">
                        <td class="px-4 py-3 text-[13px] font-medium text-ink">{{ $run->month->format('F Y') }}</td>
                        <td class="px-4 py-3">
                            @if($run->status === 'draft')
                                <span class="inline-flex items-center rounded-[5px] bg-surface px-2 py-0.5 text-[11px] font-semibold text-muted">Draft</span>
                            @elseif($run->status === 'approved')
                                <span class="inline-flex items-center rounded-[5px] bg-warn-light px-2 py-0.5 text-[11px] font-semibold text-warn-text">Approved</span>
                            @elseif($run->status === 'partially_paid')
                                <span class="inline-flex items-center rounded-[5px] bg-info-light px-2 py-0.5 text-[11px] font-semibold text-info-text">Partially Paid</span>
                            @else
                                <span class="inline-flex items-center rounded-[5px] bg-success-light px-2 py-0.5 text-[11px] font-semibold text-success-text">Paid</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-[13px] text-ink">{{ $run->entries_count }}</td>
                        <td class="px-4 py-3 text-right text-[13px] text-ink">{{ $fmt($run->total_gross) }}</td>
                        <td class="px-4 py-3 text-right text-[13px] font-semibold text-ink">{{ $fmt($run->total_net) }}</td>
                        <td class="px-4 py-3 text-[13px] text-dim">{{ $run->paid_at?->format('M d, Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('payroll.show', $run) }}" class="rounded-lg border border-line bg-surface px-3 py-1.5 text-[12px] font-medium text-dim hover:border-muted hover:text-ink transition-colors">View</a>
                                @if($run->status === 'draft')
                                    <form method="POST" action="{{ route('payroll.destroy', $run) }}" onsubmit="return confirm('Delete this draft payroll run?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-danger-border bg-danger-light px-3 py-1.5 text-[12px] font-medium text-danger hover:bg-[#ffe0e0] transition-colors cursor-pointer">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-[13px] text-muted">No payroll runs yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $runs->links() }}
    </div>
</x-layouts.app>
