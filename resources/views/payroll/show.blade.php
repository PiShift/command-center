<x-layouts.app :title="$run->month->format('F Y') . ' Payroll'">
    @php
        $fmt = fn($n) => 'MRU ' . number_format((float) $n, 2);
    @endphp

    <div
        x-data="{
            totals: {
                gross: {{ (float) $run->total_gross }},
                deductions: {{ (float) $run->total_deductions }},
                net: {{ (float) $run->total_net }},
            },
            rows: @js($run->entries->mapWithKeys(fn($entry) => [
                $entry->id => [
                    'id' => (int) $entry->id,
                    'gross' => (float) $entry->gross_amount,
                    'deductions' => (float) (
                        ((bool) $entry->skip_advances ? 0 : (float) $entry->advances_deducted)
                        + ((bool) $entry->skip_loans ? 0 : (float) $entry->loans_deducted)
                        + max(0, (float) $entry->other_deductions - ((bool) $entry->skip_unpaid_leave ? (float) $entry->unpaid_leave_deduction : 0))
                    ),
                    'net' => (float) $entry->net_amount,
                ],
            ])),
            get liveTotals() {
                const values = Object.values(this.rows || {});

                if (!values.length) {
                    return this.totals;
                }

                return values.reduce((carry, row) => {
                    carry.gross += Number(row.gross || 0);
                    carry.deductions += Number(row.deductions || 0);
                    carry.net += Number(row.net || 0);

                    return carry;
                }, { gross: 0, deductions: 0, net: 0 });
            },
            formatMoney(value) {
                return 'MRU ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }"
        @payroll-totals-updated.window="totals = $event.detail"
        @payroll-entry-preview.window="rows[$event.detail.id] = $event.detail"
    >
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('payroll.index') }}" class="text-[13px] text-muted hover:text-ink transition-colors">← Payroll Runs</a>
                <h1 class="mt-1 text-2xl font-semibold text-ink">{{ $run->month->format('F Y') }} Payroll</h1>
                <div class="mt-2">
                    @if($run->status === 'draft')
                        <span class="inline-flex items-center rounded-[5px] bg-surface px-2 py-0.5 text-[11px] font-semibold text-muted">Draft</span>
                    @elseif($run->status === 'approved')
                        <span class="inline-flex items-center rounded-[5px] bg-[#fef9ec] px-2 py-0.5 text-[11px] font-semibold text-[#9a7a1a]">Approved</span>
                    @else
                        <span class="inline-flex items-center rounded-[5px] bg-success-light px-2 py-0.5 text-[11px] font-semibold text-success-text">Paid</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($run->status === 'draft')
                    <form method="POST" action="{{ route('payroll.approve', $run) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white hover:bg-accent-hover transition-colors cursor-pointer">Approve Payroll</button>
                    </form>
                    <form method="POST" action="{{ route('payroll.destroy', $run) }}" onsubmit="return confirm('Delete this draft payroll run?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-danger-border bg-danger-light px-4 py-2 text-[13px] font-medium text-danger hover:bg-[#ffe0e0] transition-colors cursor-pointer">Delete Draft</button>
                    </form>
                @elseif($run->status === 'approved')
                    <form method="POST" action="{{ route('payroll.pay', $run) }}" class="flex items-center gap-2">
                        @csrf
                        <select name="company_account_id" required class="rounded-lg border border-line bg-surface px-3 py-2 text-[13px] text-ink focus:border-accent focus:bg-white focus:outline-none transition-colors">
                            <option value="">Select account…</option>
                            @foreach($companyAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white hover:bg-accent-hover transition-colors cursor-pointer">Mark as Paid</button>
                    </form>
                @else
                    <a href="{{ route('payroll.pdf', $run) }}" class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white hover:bg-accent-hover transition-colors">Download Summary PDF</a>
                    <span class="text-[12px] text-muted">Paid {{ $run->paid_at?->format('M d, Y H:i') }}</span>
                @endif
            </div>
        </div>

        <div class="mb-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-line bg-white p-4 shadow-card">
                <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Total Gross</p>
                <p class="mt-1 text-xl font-semibold text-ink" x-text="formatMoney(liveTotals.gross)"></p>
            </div>
            <div class="rounded-xl border border-line bg-white p-4 shadow-card">
                <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Total Deductions</p>
                <p class="mt-1 text-xl font-semibold text-danger" x-text="formatMoney(liveTotals.deductions)"></p>
            </div>
            <div class="rounded-xl border border-line bg-white p-4 shadow-card">
                <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Total Net</p>
                <p class="mt-1 text-xl font-semibold text-success-text" x-text="formatMoney(liveTotals.net)"></p>
            </div>
        </div>

        @if($skippedEmployees->isNotEmpty())
            <div class="mb-5 rounded-xl border border-danger-border bg-danger-light px-4 py-3">
                <p class="text-[12px] font-semibold text-danger">Some active employees were excluded from this run.</p>
                <div class="mt-1 text-[12px] text-danger">
                    @foreach($skippedEmployees as $skipped)
                        <div>{{ $skipped->display_name }} — no active contract.</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-line bg-white shadow-card">
            <table class="w-full min-w-[1400px] border-collapse">
                <thead>
                    <tr class="border-b border-line bg-canvas">
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Employee</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Base Salary</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Bonuses</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Advances</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Loans</th>
                        @if($run->status === 'draft')
                            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Deduction Overrides</th>
                        @endif
                        <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Other Deductions</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Net Amount</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Notes</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Status</th>
                        @if($run->status === 'draft')
                            <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($run->entries as $entry)
                        @php
                            $employeeUser = $entry->employee?->user;
                            $avatarColor = $employeeUser?->color ?? '#D97757';
                            $initials = $employeeUser?->initials ?? strtoupper(substr($entry->employee?->display_name ?? 'NA', 0, 2));
                        @endphp
                        <tr
                            x-data="payrollEntryRow({
                                id: {{ $entry->id }},
                                updateUrl: '{{ route('payroll.entries.update', [$run, $entry]) }}',
                                baseSalary: {{ (float) $entry->base_salary }},
                                advancesDeducted: {{ (float) $entry->advances_deducted }},
                                loansDeducted: {{ (float) $entry->loans_deducted }},
                                skipAdvances: @js((bool) $entry->skip_advances),
                                skipLoans: @js((bool) $entry->skip_loans),
                                skipUnpaidLeave: @js((bool) $entry->skip_unpaid_leave),
                                bonuses: {{ (float) $entry->bonuses }},
                                otherDeductions: {{ (float) $entry->other_deductions }},
                                unpaidLeaveDeduction: {{ (float) $entry->unpaid_leave_deduction }},
                                notes: @js($entry->notes),
                                csrf: '{{ csrf_token() }}'
                            })"
                            x-init="notifyTotals()"
                            x-effect="notifyTotals()"
                            class="border-b border-hairline last:border-b-0 hover:bg-canvas transition-colors"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-[12px] font-semibold text-white" style="background: {{ $avatarColor }}">{{ $initials }}</span>
                                    <div>
                                        <div class="text-[13px] font-medium text-ink">{{ $entry->employee?->display_name ?? 'Unknown employee' }}</div>
                                        <div class="text-[12px] text-muted">{{ $entry->employee?->employee_number ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-[13px] text-ink">{{ $fmt($entry->base_salary) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($run->status === 'draft')
                                    <input type="number" step="0.01" min="0" x-model.number="bonuses" class="w-28 rounded-lg border border-line bg-surface px-2 py-1 text-right text-[13px] text-ink focus:border-accent focus:bg-white focus:outline-none transition-colors">
                                @else
                                    <span class="text-[13px] text-ink">{{ $fmt($entry->bonuses) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="relative" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" :class="skipAdvances ? 'text-muted' : 'text-danger hover:text-danger'" class="text-[13px] font-medium cursor-pointer">
                                        <span :class="skipAdvances ? 'line-through' : ''" x-text="formatMoney(advancesDeducted)"></span>
                                        <span class="text-[11px]" x-text="`({{ $entry->advances->count() }})`"></span>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-line bg-white p-3 text-left shadow-[0_4px_20px_rgba(20,20,19,0.10)]">
                                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Advances</p>
                                        @forelse($entry->advances as $advance)
                                            <div class="mb-1.5 border-b border-hairline pb-1.5 last:mb-0 last:border-b-0 last:pb-0">
                                                <p class="text-[12px] text-dim">{{ $advance->date?->format('M d, Y') }} · {{ $advance->reason }}</p>
                                                <p class="text-[12px] font-medium text-ink">{{ $fmt($advance->amount) }}</p>
                                            </div>
                                        @empty
                                            <p class="text-[12px] text-muted">No advances linked.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="relative" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" :class="skipLoans ? 'text-muted' : 'text-danger hover:text-danger'" class="text-[13px] font-medium cursor-pointer">
                                        <span :class="skipLoans ? 'line-through' : ''" x-text="formatMoney(loansDeducted)"></span>
                                        <span class="text-[11px]" x-text="`({{ $entry->loanRepayments->count() }})`"></span>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 z-20 mt-2 w-80 rounded-xl border border-line bg-white p-3 text-left shadow-[0_4px_20px_rgba(20,20,19,0.10)]">
                                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.06em] text-muted">Loan Installments</p>
                                        @forelse($entry->loanRepayments as $repayment)
                                            <div class="mb-1.5 border-b border-hairline pb-1.5 last:mb-0 last:border-b-0 last:pb-0">
                                                <p class="text-[12px] text-dim">{{ $repayment->loan?->title ?? 'Loan' }}</p>
                                                <p class="text-[12px] font-medium text-ink">Installment: {{ $fmt($repayment->amount) }}</p>
                                                <p class="text-[12px] text-muted">Remaining balance after: {{ $fmt($repayment->loan?->amount_remaining ?? 0) }}</p>
                                            </div>
                                        @empty
                                            <p class="text-[12px] text-muted">No loan installments linked.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </td>
                            @if($run->status === 'draft')
                                <td class="px-4 py-3">
                                    <div class="space-y-2">
                                        <label class="inline-flex items-center gap-2 text-[12px] text-dim">
                                            <input type="checkbox" x-model="skipAdvances" class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                            <span>Skip advances</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-[12px] text-dim">
                                            <input type="checkbox" x-model="skipLoans" class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                            <span>Skip loans</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-[12px] text-dim">
                                            <input type="checkbox" x-model="skipUnpaidLeave" class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                            <span>Skip unpaid leave</span>
                                        </label>

                                        <p x-show="skipAdvances" x-cloak class="text-[11px] font-medium text-[#9a7a1a]">
                                            Advances will not be deducted this month — they carry over to next payroll.
                                        </p>
                                        <p x-show="skipLoans" x-cloak class="text-[11px] font-medium text-[#9a7a1a]">
                                            Loan installment skipped this month.
                                        </p>
                                        <p x-show="skipUnpaidLeave" x-cloak class="text-[11px] font-medium text-[#9a7a1a]">
                                            Unpaid leave deduction will be excluded from net pay.
                                        </p>
                                        <p class="text-[11px] text-muted">
                                            Unpaid leave deduction: {{ $fmt($entry->unpaid_leave_deduction) }}
                                        </p>
                                    </div>
                                </td>
                            @endif
                            <td class="px-4 py-3 text-right">
                                @if($run->status === 'draft')
                                    <input type="number" step="0.01" min="0" x-model.number="otherDeductions" class="w-28 rounded-lg border border-line bg-surface px-2 py-1 text-right text-[13px] text-ink focus:border-accent focus:bg-white focus:outline-none transition-colors">
                                @else
                                    <span class="text-[13px] text-ink">{{ $fmt($entry->other_deductions) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-[13px] font-semibold text-ink" x-text="formatMoney(netPreview)"></td>
                            <td class="px-4 py-3">
                                @if($run->status === 'draft')
                                    <textarea rows="2" x-model="notes" class="w-64 rounded-lg border border-line bg-surface px-2 py-1 text-[12px] text-ink focus:border-accent focus:bg-white focus:outline-none transition-colors"></textarea>
                                @else
                                    <p class="max-w-64 text-[12px] text-dim">{{ $entry->notes ?: '—' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($entry->status === 'paid')
                                    <span class="inline-flex items-center rounded-[5px] bg-success-light px-2 py-0.5 text-[11px] font-semibold text-success-text">Paid</span>
                                @else
                                    <span class="inline-flex items-center rounded-[5px] bg-surface px-2 py-0.5 text-[11px] font-semibold text-muted">Pending</span>
                                @endif
                            </td>
                            @if($run->status === 'draft')
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="save()" :disabled="saving" class="rounded-lg border border-line bg-surface px-3 py-1.5 text-[12px] font-medium text-dim hover:border-muted hover:text-ink disabled:opacity-60 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                        <span x-show="!saving">Save</span>
                                        <span x-show="saving" x-cloak>Saving...</span>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $run->status === 'draft' ? 11 : 9 }}" class="px-4 py-12 text-center text-[13px] text-muted">No entries in this payroll run.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($run->status === 'draft')
        <script>
            function payrollEntryRow(config) {
                return {
                    ...config,
                    saving: false,
                    formatMoney(value) {
                        return 'MRU ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },
                    get grossPreview() {
                        return Number(this.baseSalary || 0) + Number(this.bonuses || 0);
                    },
                    get unpaidLeaveOffset() {
                        return this.skipUnpaidLeave ? Number(this.unpaidLeaveDeduction || 0) : 0;
                    },
                    get effectiveOtherDeductions() {
                        return Math.max(0, Number(this.otherDeductions || 0) - this.unpaidLeaveOffset);
                    },
                    get netPreview() {
                        return this.grossPreview
                            - (this.skipAdvances ? 0 : Number(this.advancesDeducted || 0))
                            - (this.skipLoans ? 0 : Number(this.loansDeducted || 0))
                            - this.effectiveOtherDeductions;
                    },
                    get deductionsPreview() {
                        return (this.skipAdvances ? 0 : Number(this.advancesDeducted || 0))
                            + (this.skipLoans ? 0 : Number(this.loansDeducted || 0))
                            + this.effectiveOtherDeductions;
                    },
                    notifyTotals() {
                        window.dispatchEvent(new CustomEvent('payroll-entry-preview', {
                            detail: {
                                id: this.id,
                                gross: this.grossPreview,
                                deductions: this.deductionsPreview,
                                net: this.netPreview,
                            }
                        }));
                    },
                    async save() {
                        this.saving = true;
                        try {
                            const formData = new FormData();
                            formData.append('_method', 'PATCH');
                            formData.append('bonuses', this.bonuses ?? 0);
                            formData.append('other_deductions', this.otherDeductions ?? 0);
                            formData.append('skip_advances', this.skipAdvances ? '1' : '0');
                            formData.append('skip_loans', this.skipLoans ? '1' : '0');
                            formData.append('skip_unpaid_leave', this.skipUnpaidLeave ? '1' : '0');
                            formData.append('notes', this.notes ?? '');

                            const response = await fetch(this.updateUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf,
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error('Failed to update payroll entry');
                            }

                            const data = await response.json();
                            this.bonuses = Number(data.entry.bonuses || 0);
                            this.otherDeductions = Number(data.entry.other_deductions || 0);
                            this.skipAdvances = Boolean(data.entry.skip_advances);
                            this.skipLoans = Boolean(data.entry.skip_loans);
                            this.skipUnpaidLeave = Boolean(data.entry.skip_unpaid_leave);
                            this.notes = data.entry.notes || '';

                            window.dispatchEvent(new CustomEvent('payroll-totals-updated', {
                                detail: {
                                    gross: Number(data.run.total_gross || 0),
                                    deductions: Number(data.run.total_deductions || 0),
                                    net: Number(data.run.total_net || 0),
                                }
                            }));
                        } catch (error) {
                            alert('Unable to update entry right now.');
                        } finally {
                            this.saving = false;
                        }
                    }
                };
            }
        </script>
    @endif
</x-layouts.app>
