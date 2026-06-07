<x-layouts.app title="Generate Payroll">
    @php
        $fmt = fn($n) => 'MRU ' . number_format((float) $n, 2);
        $years = range(now()->year - 1, now()->year + 1);
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-5">
            <a href="{{ route('payroll.index') }}" class="text-[13px] text-muted hover:text-ink transition-colors">← Payroll Runs</a>
            <h1 class="mt-2 text-2xl font-semibold text-ink">Generate Payroll</h1>
            <p class="mt-1 text-[13px] text-muted">Select month and year to generate a new payroll run.</p>
        </div>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-danger-border bg-danger-light px-4 py-3 text-[13px] text-danger">{{ session('error') }}</div>
        @endif

        <div class="rounded-xl border border-line bg-white p-6 shadow-card">
            <form method="GET" action="{{ route('payroll.create') }}" class="mb-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Month</label>
                    <select name="month" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[13px] text-ink focus:border-accent focus:bg-white focus:outline-none transition-colors">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($m === (int) $selectedMonthNumber)>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Year</label>
                    <select name="year" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[13px] text-ink focus:border-accent focus:bg-white focus:outline-none transition-colors">
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($year === (int) $selectedYearNumber)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="rounded-lg border border-line bg-surface px-3 py-1.5 text-[12px] font-medium text-dim hover:border-muted hover:text-ink transition-colors cursor-pointer">Refresh Preview</button>
                </div>
            </form>

            @if($payrollExistsForMonth)
                <div class="mb-4 rounded-lg border border-danger-border bg-danger-light px-4 py-3 text-[13px] text-danger">
                    Payroll run already exists for {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}.
                </div>
            @endif

            <div class="mb-5 rounded-lg border border-line bg-canvas p-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Preview</p>
                <div class="mt-2 grid gap-3 md:grid-cols-2">
                    <div>
                        <p class="text-[12px] text-dim">Active employees included</p>
                        <p class="text-lg font-semibold text-ink">{{ $activeEmployeesPreview }}</p>
                    </div>
                    <div>
                        <p class="text-[12px] text-dim">Estimated gross total</p>
                        <p class="text-lg font-semibold text-ink">{{ $fmt($estimatedTotal) }}</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('payroll.store') }}" class="flex items-center justify-end gap-2">
                @csrf
                <input type="hidden" name="month_number" value="{{ $selectedMonthNumber }}">
                <input type="hidden" name="year_number" value="{{ $selectedYearNumber }}">

                <a href="{{ route('payroll.index') }}" class="rounded-lg border border-line bg-surface px-4 py-2 text-[13px] font-medium text-dim hover:border-muted hover:text-ink transition-colors">Cancel</a>
                <button type="submit" @disabled($payrollExistsForMonth) class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white hover:bg-accent-hover disabled:cursor-not-allowed disabled:opacity-60 transition-colors cursor-pointer">Generate Payroll</button>
            </form>
        </div>
    </div>
</x-layouts.app>
