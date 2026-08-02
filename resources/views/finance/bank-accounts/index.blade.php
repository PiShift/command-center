<x-layouts.app title="Bank Accounts">
    <div x-data="bankAccountTransfers({
        accounts: @js($accounts->map(fn($account) => [
            'id' => (int) $account->id,
            'name' => $account->name,
            'currency' => $account->currency,
            'balance' => (float) ($account->computed_balance ?? $account->balance ?? 0),
        ])),
        defaultDate: '{{ now()->toDateString() }}'
    })">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink leading-tight">Company Bank Accounts</h1>
            <p class="text-sm text-muted mt-1">Track account balances and review ledgers.</p>
        </div>

        @if(auth()->user()?->hasPermission('finance.manage'))
            <div class="flex items-center gap-2">
                <button type="button"
                   @click="open = true"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-line bg-surface hover:bg-hairline text-ink text-sm font-medium transition-colors cursor-pointer">
                    <span>↔</span>
                    <span>Transfer Funds</span>
                </button>
                <a href="{{ route('bank-accounts.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-accent hover:bg-accent-hover text-white text-sm font-medium transition-colors">
                    <span>+</span>
                    <span>New Account</span>
                </a>
            </div>
        @endif
    </div>

    @include('components.flash')

    @if($accounts->isEmpty())
        <div class="rounded-xl border border-hairline bg-card shadow-card p-10 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-surface border border-line flex items-center justify-center text-muted mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75h19.5m-18 3h16.5m-16.5 3h16.5m-16.5 3h12" />
                </svg>
            </div>
            <p class="text-base text-dim font-medium">No bank accounts yet</p>
            <p class="text-sm text-muted mt-1">Create your first company account to start tracking transactions.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($accounts as $account)
                @php
                    $balance = (float) ($account->computed_balance ?? $account->balance ?? 0);
                    $isPositive = $balance >= 0;
                @endphp

                <div class="rounded-xl border border-hairline bg-card shadow-card hover:shadow-card-hover hover:border-line transition-all p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-ink leading-snug">{{ $account->name }}</h2>
                            <p class="text-sm text-muted mt-1">{{ $account->bank_name ?: 'Cash account' }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xxs font-semibold bg-surface text-dim border border-line">
                            {{ $account->currency }}
                        </span>
                    </div>

                    <div class="mt-5">
                        <p class="text-xxs font-bold uppercase tracking-[0.06em] text-muted">Current Balance</p>
                        <p class="mt-1 text-2xl font-semibold {{ $isPositive ? 'text-success-text' : 'text-danger' }}">
                            {{ number_format($balance, 2) }}
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-hairline flex items-center justify-between gap-2">
                        <a href="{{ route('bank-accounts.show', $account) }}" class="text-sm font-medium text-accent hover:text-accent-hover transition-colors">
                            View Ledger
                        </a>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('bank-accounts.edit', $account) }}"
                               class="px-3 py-1.5 rounded-md border border-line bg-surface hover:bg-hairline text-sm font-medium text-ink transition-colors">
                                Edit
                            </a>

                            @unless($account->is_system)
                                <form method="POST" action="{{ route('bank-accounts.destroy', $account) }}" onsubmit="return confirm('Delete this account?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-md border border-danger-border bg-danger-light hover:bg-[#ffe0e0] text-sm font-medium text-danger transition-colors cursor-pointer">
                                        Delete
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div
        x-show="open"
        x-cloak
        @keydown.escape.window="close()"
        class="fixed inset-0 z-50 flex items-center justify-center px-4"
    >
        <div class="absolute inset-0 bg-black/45" @click="close()"></div>

        <div class="relative w-full max-w-lg rounded-2xl border border-line bg-card shadow-[0_20px_60px_rgba(0,0,0,0.18)]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-hairline">
                <h2 class="text-base font-semibold text-ink">Transfer Between Accounts</h2>
                <button type="button" @click="close()" class="h-7 w-7 rounded-full bg-black/10 hover:bg-black/15 text-dim hover:text-ink transition-colors cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('bank-accounts.transfer.store') }}" class="p-5 space-y-4">
                @csrf

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">From Account</label>
                    <select name="from_account_id" x-model.number="fromAccountId" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink focus:border-accent focus:bg-card focus:outline-none transition-colors">
                        <option value="">Select source account</option>
                        <template x-for="account in accounts" :key="'from-'+account.id">
                            <option :value="account.id" x-text="`${account.name} (${fmt(account.balance)})`"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">To Account</label>
                    <select name="to_account_id" x-model.number="toAccountId" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink focus:border-accent focus:bg-card focus:outline-none transition-colors">
                        <option value="">Select destination account</option>
                        <template x-for="account in toAccounts" :key="'to-'+account.id">
                            <option :value="account.id" x-text="`${account.name} (${fmt(account.balance)})`"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Amount</label>
                        <input name="amount" type="number" step="0.01" min="0.01" x-model.number="amount" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink focus:border-accent focus:bg-card focus:outline-none transition-colors" placeholder="0.00">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Date</label>
                        <input name="date" type="date" x-model="date" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink focus:border-accent focus:bg-card focus:outline-none transition-colors">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Reference</label>
                    <input name="reference" type="text" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink focus:border-accent focus:bg-card focus:outline-none transition-colors" placeholder="Optional">
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink focus:border-accent focus:bg-card focus:outline-none transition-colors" placeholder="Optional"></textarea>
                </div>

                <div x-show="fromAccount && toAccount && amount > 0" x-cloak class="rounded-lg border border-hairline bg-canvas px-3 py-2 text-[12px] text-dim">
                    After transfer: From account will have <span class="font-semibold text-ink" x-text="fmt(afterFrom)"></span>,
                    To account will have <span class="font-semibold text-ink" x-text="fmt(afterTo)"></span>
                </div>

                <div class="pt-1 flex items-center justify-end gap-2">
                    <button type="button" @click="close()" class="px-4 py-2 rounded-md border border-line bg-surface hover:bg-hairline text-[13px] font-medium text-ink transition-colors cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-md bg-accent hover:bg-accent-hover text-[13px] font-medium text-white transition-colors cursor-pointer">Transfer Funds</button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <script>
        function bankAccountTransfers(config) {
            return {
                open: false,
                accounts: config.accounts || [],
                defaultDate: config.defaultDate,
                fromAccountId: '',
                toAccountId: '',
                amount: '',
                date: config.defaultDate,
                get fromAccount() {
                    return this.accounts.find(a => Number(a.id) === Number(this.fromAccountId)) || null;
                },
                get toAccount() {
                    return this.accounts.find(a => Number(a.id) === Number(this.toAccountId)) || null;
                },
                get toAccounts() {
                    return this.accounts.filter(a => Number(a.id) !== Number(this.fromAccountId));
                },
                get afterFrom() {
                    if (!this.fromAccount) return 0;
                    return Number(this.fromAccount.balance || 0) - Number(this.amount || 0);
                },
                get afterTo() {
                    if (!this.toAccount) return 0;
                    return Number(this.toAccount.balance || 0) + Number(this.amount || 0);
                },
                fmt(value) {
                    return 'MRU ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                close() {
                    this.open = false;
                },
            }
        }
    </script>
</x-layouts.app>
