<x-layouts.app :title="$account->exists ? 'Edit Bank Account' : 'New Bank Account'">
    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('bank-accounts.index') }}" class="text-sm text-muted hover:text-dim transition-colors">← Bank Accounts</a>
            <h1 class="text-2xl font-semibold text-ink leading-tight">{{ $account->exists ? 'Edit Bank Account' : 'New Bank Account' }}</h1>
        </div>
    </div>

    @include('components.flash')

    <div class="max-w-2xl rounded-xl border border-hairline bg-card shadow-card p-5">
        <form method="POST" action="{{ $account->exists ? route('bank-accounts.update', $account) : route('bank-accounts.store') }}" class="space-y-4">
            @csrf
            @if($account->exists)
                @method('PUT')
            @endif

            <div>
                <label for="name" class="block text-xxs font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Name</label>
                <input id="name" name="name" type="text" required
                       value="{{ old('name', $account->name) }}"
                       class="w-full rounded-md border border-line bg-surface focus:bg-card focus:border-accent px-3 py-2.5 text-base text-ink placeholder:text-muted placeholder:italic transition-colors" />
                @error('name')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="bank_name" class="block text-xxs font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Bank Name</label>
                <input id="bank_name" name="bank_name" type="text"
                       value="{{ old('bank_name', $account->bank_name) }}"
                       class="w-full rounded-md border border-line bg-surface focus:bg-card focus:border-accent px-3 py-2.5 text-base text-ink placeholder:text-muted placeholder:italic transition-colors" />
                @error('bank_name')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="account_number" class="block text-xxs font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Account Number</label>
                <input id="account_number" name="account_number" type="text"
                       value="{{ old('account_number', $account->account_number) }}"
                       class="w-full rounded-md border border-line bg-surface focus:bg-card focus:border-accent px-3 py-2.5 text-base text-ink placeholder:text-muted placeholder:italic transition-colors" />
                @error('account_number')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="currency" class="block text-xxs font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Currency</label>
                <input id="currency" name="currency" type="text" maxlength="10"
                       value="{{ old('currency', $account->currency ?: 'MRU') }}"
                       class="w-full rounded-md border border-line bg-surface focus:bg-card focus:border-accent px-3 py-2.5 text-base text-ink placeholder:text-muted placeholder:italic transition-colors" />
                @error('currency')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="notes" class="block text-xxs font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                          class="w-full rounded-md border border-line bg-surface focus:bg-card focus:border-accent px-3 py-2.5 text-base text-ink placeholder:text-muted placeholder:italic transition-colors resize-y">{{ old('notes', $account->notes) }}</textarea>
                @error('notes')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="{{ route('bank-accounts.index') }}"
                   class="px-4 py-2 rounded-md border border-line bg-surface hover:bg-hairline text-sm font-medium text-ink transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 rounded-md bg-accent hover:bg-accent-hover text-white text-sm font-medium transition-colors cursor-pointer">
                    {{ $account->exists ? 'Save Changes' : 'Create Account' }}
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
