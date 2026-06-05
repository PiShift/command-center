<x-layouts.app title="Bank Accounts">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink leading-tight">Company Bank Accounts</h1>
            <p class="text-sm text-muted mt-1">Track account balances and review ledgers.</p>
        </div>

        @if(auth()->user()?->hasPermission('finance.manage'))
            <a href="{{ route('bank-accounts.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-accent hover:bg-accent-hover text-white text-sm font-medium transition-colors">
                <span>+</span>
                <span>New Account</span>
            </a>
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
</x-layouts.app>
