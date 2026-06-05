<x-layouts.app :title="$account->name . ' Ledger'">
    @php
        $pageItems = collect($transactions->items());
        $oldestToNewest = $pageItems->reverse()->values();

        $running = (float) $openingBalance;
        $computedBySourceId = [];

        foreach ($oldestToNewest as $tx) {
            $running += ($tx['type'] === 'in') ? (float) $tx['amount'] : -1 * (float) $tx['amount'];
            $computedBySourceId[$tx['source'] . '_' . $tx['source_id']] = $running;
        }
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('bank-accounts.index') }}" class="text-sm text-muted hover:text-dim transition-colors">← Bank Accounts</a>
                <h1 class="text-2xl font-semibold text-ink leading-tight">{{ $account->name }}</h1>
            </div>
            <p class="text-sm text-muted mt-1">{{ $account->bank_name ?: 'Cash account' }}</p>
        </div>

        <div class="text-right">
            <p class="text-xxs font-bold uppercase tracking-[0.06em] text-muted">Current Balance</p>
            <p class="text-2xl font-semibold {{ $currentBalance >= 0 ? 'text-success-text' : 'text-danger' }}">
                {{ number_format($currentBalance, 2) }} {{ $account->currency }}
            </p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 mb-5">
        <div class="rounded-xl border border-hairline bg-card shadow-card p-4">
            <p class="text-xxs font-bold uppercase tracking-[0.06em] text-muted">Total In</p>
            <p class="mt-1 text-lg font-semibold text-success-text">{{ number_format($totalIn, 2) }} {{ $account->currency }}</p>
        </div>
        <div class="rounded-xl border border-hairline bg-card shadow-card p-4">
            <p class="text-xxs font-bold uppercase tracking-[0.06em] text-muted">Total Out</p>
            <p class="mt-1 text-lg font-semibold text-danger">{{ number_format($totalOut, 2) }} {{ $account->currency }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-hairline bg-card shadow-card overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-canvas border-b border-line">
                    <th class="px-4 py-2.5 text-left text-xxs font-bold uppercase tracking-[0.06em] text-muted">Date</th>
                    <th class="px-4 py-2.5 text-left text-xxs font-bold uppercase tracking-[0.06em] text-muted">Description</th>
                    <th class="px-4 py-2.5 text-left text-xxs font-bold uppercase tracking-[0.06em] text-muted">Type</th>
                    <th class="px-4 py-2.5 text-right text-xxs font-bold uppercase tracking-[0.06em] text-muted">Amount</th>
                    <th class="px-4 py-2.5 text-right text-xxs font-bold uppercase tracking-[0.06em] text-muted">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    @php
                        $key = $tx['source'] . '_' . $tx['source_id'];
                        $lineBalance = $computedBySourceId[$key] ?? null;
                    @endphp
                    <tr class="border-b border-hairline last:border-b-0 hover:bg-canvas transition-colors">
                        <td class="px-4 py-3 text-sm text-dim">
                            {{ \Illuminate\Support\Carbon::parse($tx['date'])->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-ink">
                            @if($tx['source'] === 'invoice_payment')
                                <a href="{{ route('invoices.show', $tx['invoice_id']) }}" class="text-accent hover:text-accent-hover transition-colors">
                                    {{ $tx['description'] }}
                                </a>
                            @elseif($tx['source'] === 'expense')
                                <a href="{{ route('expenses.show', $tx['source_id']) }}" class="text-accent hover:text-accent-hover transition-colors">
                                    {{ $tx['description'] }}
                                </a>
                            @else
                                {{ $tx['description'] }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xxs font-semibold {{ $tx['type'] === 'in' ? 'bg-success-light text-success-text' : 'bg-danger-light text-danger' }}">
                                {{ strtoupper($tx['type']) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium {{ $tx['type'] === 'in' ? 'text-success-text' : 'text-danger' }}">
                            {{ $tx['type'] === 'in' ? '+' : '-' }}{{ number_format((float) $tx['amount'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium {{ ($lineBalance ?? 0) >= 0 ? 'text-success-text' : 'text-danger' }}">
                            {{ number_format((float) ($lineBalance ?? 0), 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-muted">
                            No transactions found for this account.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</x-layouts.app>
