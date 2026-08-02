<x-layouts.app title="Expenses">

@php
    $allExpenses   = \App\Models\Expense::with(['category', 'project', 'recurringCharge'])->orderByDesc('expense_date')->paginate(50);
    $allCharges    = \App\Models\RecurringCharge::with(['category', 'project'])->orderBy('name')->get();
    $allCategories = \App\Models\ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

    $statusStyles = [
        'draft'     => 'background:#fff8ee; color:#9a5a1a',
        'confirmed' => 'background:#edf7f2; color:#2e7d55',
    ];
@endphp

<style>
    @media (max-width: 768px) {
        .expenses-overview-header-actions,
        .expenses-overview-pending-row,
        .expenses-overview-pending-main,
        .expenses-overview-pending-actions,
        .expenses-overview-table-actions {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .expenses-overview-header-actions {
            width: 100%;
            gap: 8px !important;
        }

        .expenses-overview-header-actions > * {
            width: 100%;
        }

        .expenses-overview-header-actions a,
        .expenses-overview-header-actions button {
            width: 100%;
            justify-content: center;
        }

        .expenses-overview-tabs {
            width: 100%;
            overflow-x: auto;
            white-space: nowrap;
        }

        .expenses-overview-tabs button {
            flex-shrink: 0;
        }

        .expenses-overview-pending-row {
            gap: 8px;
        }

        .expenses-overview-pending-actions form,
        .expenses-overview-table-actions form,
        .expenses-overview-table-actions a {
            width: 100%;
        }

        .expenses-overview-pending-actions button,
        .expenses-overview-table-actions button,
        .expenses-overview-table-actions a {
            width: 100%;
            text-align: center;
            justify-content: center;
            display: inline-flex;
        }
    }

    @media (max-width: 768px) {
        .expenses-overview-container {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    }
</style>

<div class="expenses-overview-container" x-data="{ tab: '{{ request('tab', 'overview') }}', month: '{{ $month->format('Y-m') }}' }"
     style="max-width:1200px;margin:0 auto;padding:32px 0">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:14px">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#141413;margin:0">Expenses</h1>
            <p style="font-size:13px;color:#8c8c8a;margin:4px 0 0" x-show="tab === 'overview'">{{ $month->format('F Y') }}</p>
        </div>
        <div class="expenses-overview-header-actions" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            {{-- New expense — always visible --}}
            <a href="{{ route('expenses.create') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#D97757;border:none;border-radius:8px;font-size:13px;font-weight:500;color:#fff;text-decoration:none;white-space:nowrap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Expense
            </a>
            {{-- Month picker (only relevant in overview tab) --}}
            <form method="GET" x-show="tab === 'overview'" style="display:inline-flex;align-items:center;gap:8px">
                <input type="hidden" name="tab" value="overview">
                <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                       style="padding:8px 12px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none"
                       onchange="this.form.submit()">
            </form>
            {{-- Generate drafts (overview tab) --}}
            <form method="POST" action="{{ route('expenses.generate-drafts') }}" x-show="tab === 'overview'" style="display:inline-flex">
                @csrf
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:13px;font-weight:500;color:#141413;cursor:pointer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Generate Drafts
                </button>
            </form>
            {{-- Manage Budgets (overview tab) --}}
            <span x-show="tab === 'overview'" style="display:inline-flex">
                <a href="{{ route('monthly-budgets.index', ['month' => $month->format('Y-m')]) }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:13px;font-weight:500;color:#141413;text-decoration:none;white-space:nowrap">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    Budgets
                </a>
            </span>
        </div>
    </div>

    {{-- Tab switcher --}}
    <div class="expenses-overview-tabs inline-flex bg-surface rounded-lg p-1 gap-0.5 mb-7">
        <button type="button" @click="tab = 'overview'"
                :class="tab === 'overview'
                    ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]'
                    : 'bg-transparent text-muted hover:text-dim'"
                class="px-4 py-1.5 border-0 rounded-md text-[13px] font-medium cursor-pointer transition-all duration-150">
            Overview
        </button>
        <button type="button" @click="tab = 'all'"
                :class="tab === 'all'
                    ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]'
                    : 'bg-transparent text-muted hover:text-dim'"
                class="px-4 py-1.5 border-0 rounded-md text-[13px] font-medium cursor-pointer transition-all duration-150">
            All Expenses
        </button>
        <button type="button" @click="tab = 'recurring'"
                :class="tab === 'recurring'
                    ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]'
                    : 'bg-transparent text-muted hover:text-dim'"
                class="px-4 py-1.5 border-0 rounded-md text-[13px] font-medium cursor-pointer transition-all duration-150">
            Recurring
        </button>
        <button type="button" @click="tab = 'categories'"
                :class="tab === 'categories'
                    ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]'
                    : 'bg-transparent text-muted hover:text-dim'"
                class="px-4 py-1.5 border-0 rounded-md text-[13px] font-medium cursor-pointer transition-all duration-150">
            Categories
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:#edf7f2;border:1px solid #b5deca;border-radius:8px;padding:11px 16px;color:#2e7d55;font-size:13px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fdf0f0;border:1px solid #f5c6c6;border-radius:8px;padding:11px 16px;color:#b94040;font-size:13px;margin-bottom:20px">
            {{ session('error') }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- TAB 1: OVERVIEW --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div x-show="tab === 'overview'" x-cloak>

        {{-- Totals summary bar --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px">
            @php
                $summaryCards = [
                    ['label' => 'Expected',  'value' => $totals['expected_amount'],  'color' => '#3a6fba'],
                    ['label' => 'Recurring', 'value' => $totals['recurring_amount'], 'color' => '#8c8c8a'],
                    ['label' => 'Actual',    'value' => $totals['actual_amount'],    'color' => '#D97757'],
                    ['label' => 'Variance',  'value' => $totals['variance'],         'color' => $totals['variance'] >= 0 ? '#2e7d55' : '#b94040'],
                ];
            @endphp
            @foreach($summaryCards as $card)
                <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:20px">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:8px">{{ $card['label'] }}</div>
                    <div style="font-size:22px;font-weight:700;color:{{ $card['color'] }}">
                        {{ number_format($card['value'], 2) }}
                        <span style="font-size:13px;font-weight:500;color:#8c8c8a">MRU</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pending drafts --}}
        @if($drafts->count())
            <div style="background:#fffbf2;border:1px solid #f5e0a8;border-radius:12px;padding:20px;margin-bottom:24px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                    <span style="font-size:14px;font-weight:600;color:#9a5a1a">{{ $drafts->count() }} Pending Draft(s)</span>
                    <form method="POST" action="{{ route('expenses.bulk-confirm') }}">
                        @csrf
                        @foreach($drafts as $d)
                            <input type="hidden" name="ids[]" value="{{ $d->id }}">
                        @endforeach
                        <button type="submit"
                                style="padding:6px 14px;background:#9a5a1a;border:none;border-radius:7px;font-size:12px;font-weight:600;color:#fff;cursor:pointer">
                            Confirm All
                        </button>
                    </form>
                </div>
                @foreach($drafts as $d)
                    <div class="expenses-overview-pending-row" style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f5e0a8;gap:10px">
                        <div class="expenses-overview-pending-main" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            @if($d->category)
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $d->category->color }};display:inline-block"></span>
                            @endif
                            <span style="font-size:14px;color:#141413">{{ $d->title }}</span>
                            <span style="font-size:12px;color:#8c8c8a">{{ $d->expense_date->format('d M') }}</span>
                        </div>
                        <div class="expenses-overview-pending-actions" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                            <span style="font-size:14px;font-weight:600;color:#141413">
                                {{ number_format((float) $d->amount_mru, 2) }} MRU
                                @if(strtoupper((string) $d->currency) !== 'MRU')
                                    <span style="font-size:12px;color:#8c8c8a">({{ $d->originalCurrencyLabel() }})</span>
                                @endif
                            </span>
                            <form method="POST" action="{{ route('expenses.confirm', $d) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        style="padding:4px 10px;background:#edf7f2;border:1px solid #b5deca;border-radius:5px;font-size:12px;color:#2e7d55;cursor:pointer">
                                    Confirm
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Per-category breakdown --}}
        <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;overflow:hidden;margin-bottom:32px">
            <div style="padding:16px 20px;border-bottom:1px solid #eeeee9">
                <h2 style="font-size:15px;font-weight:600;color:#141413;margin:0">Category Breakdown</h2>
            </div>
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:760px">
                <thead>
                    <tr style="border-bottom:1px solid #eeeee9;background:#F5F4EF">
                        <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Category</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Budget</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Recurring</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Actual</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Variance</th>
                        <th style="padding:11px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $cat      = $row['category'];
                            $budget   = $row['budget_amount'];
                            $rec      = $row['recurring_amount'];
                            $actual   = $row['actual_amount'];
                            $expected = $row['expected_total'];
                            $variance = $row['variance'];
                            $state    = $row['state'];
                            if ($state === 'planned' && $expected > 0) {
                                $pct      = min(100, round(($actual / $expected) * 100));
                                $barColor = $pct >= 100 ? '#b94040' : ($pct >= 80 ? '#e67e22' : '#2e7d55');
                            } else {
                                $pct = 0; $barColor = '#2e7d55';
                            }
                        @endphp
                        <tr style="border-bottom:1px solid #eeeee9"
                            onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background=''">
                            <td style="padding:13px 16px">
                                <div style="display:flex;align-items:center;gap:8px">
                                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $cat->color }};display:inline-block;flex-shrink:0"></span>
                                    <span style="font-size:14px;font-weight:500;color:#141413">{{ $cat->name }}</span>
                                    @if($state === 'unplanned')
                                        <span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;background:#F5F4EF;color:#8c8c8a">Unplanned</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding:13px 16px;text-align:right;font-size:13px;color:#5c5c5a">{{ $budget > 0 ? number_format($budget, 2) : '—' }}</td>
                            <td style="padding:13px 16px;text-align:right;font-size:13px;color:#5c5c5a">{{ $rec > 0 ? number_format($rec, 2) : '—' }}</td>
                            <td style="padding:13px 16px;text-align:right;font-size:14px;font-weight:600;color:#141413">{{ number_format($actual, 2) }}</td>
                            <td style="padding:13px 16px;text-align:right;font-size:13px;font-weight:500">
                                @if($state === 'planned')
                                    <span style="color:{{ $variance >= 0 ? '#2e7d55' : '#b94040' }}">
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 2) }}
                                    </span>
                                @else
                                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;background:#F5F4EF;color:#8c8c8a">—</span>
                                @endif
                            </td>
                            <td style="padding:13px 16px;min-width:140px">
                                @if($state === 'planned' && $expected > 0)
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div style="flex:1;height:6px;background:#eeeee9;border-radius:3px;overflow:hidden">
                                            <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:3px;transition:width .3s"></div>
                                        </div>
                                        <span style="font-size:11px;color:#8c8c8a;min-width:32px;text-align:right">{{ $pct }}%</span>
                                    </div>
                                @else
                                    <span style="font-size:12px;color:#c0bfba">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#F5F4EF;border-top:1px solid #e5e4df">
                        <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#141413">Total</td>
                        <td style="padding:12px 16px;text-align:right;font-size:13px;font-weight:600;color:#141413">{{ number_format($totals['budget_amount'], 2) }}</td>
                        <td style="padding:12px 16px;text-align:right;font-size:13px;font-weight:600;color:#141413">{{ number_format($totals['recurring_amount'], 2) }}</td>
                        <td style="padding:12px 16px;text-align:right;font-size:14px;font-weight:700;color:#141413">{{ number_format($totals['actual_amount'], 2) }}</td>
                        <td style="padding:12px 16px;text-align:right;font-size:13px;font-weight:600;color:{{ $totals['variance'] >= 0 ? '#2e7d55' : '#b94040' }}">
                            {{ $totals['variance'] >= 0 ? '+' : '' }}{{ number_format($totals['variance'], 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        {{-- 6-month trend --}}
        <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:24px">
            <h2 style="font-size:15px;font-weight:600;color:#141413;margin:0 0 20px">6-Month Spending Trend</h2>
            @php
                $maxVal = max(array_merge(array_column($trend, 'actual'), [1]));
            @endphp
            <div style="display:flex;align-items:flex-end;gap:12px;height:160px">
                @foreach($trend as $t)
                    @php $barH = max(4, round(($t['actual'] / $maxVal) * 140)); @endphp
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end">
                        <span style="font-size:10px;color:#8c8c8a;text-align:center">{{ number_format($t['actual'] / 1000, 1) }}k</span>
                        <div style="width:100%;background:{{ $t['label'] === $month->format('M Y') ? '#D97757' : '#e5e4df' }};border-radius:4px 4px 0 0;height:{{ $barH }}px;transition:height .3s"></div>
                        <span style="font-size:10px;color:#8c8c8a;text-align:center;white-space:nowrap">{{ $t['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>{{-- /overview tab --}}

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- TAB 2: ALL EXPENSES --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div x-show="tab === 'all'" x-cloak>

        {{-- Draft banner --}}
        @php $pendingDrafts = $allExpenses->getCollection()->where('status', 'draft'); @endphp
        @if($pendingDrafts->count())
            <div style="background:#fffbf2;border:1px solid #f5e0a8;border-radius:12px;padding:18px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:14px;font-weight:600;color:#9a5a1a">{{ $pendingDrafts->count() }} draft(s) pending confirmation</span>
                <form method="POST" action="{{ route('expenses.bulk-confirm') }}">
                    @csrf
                    @foreach($pendingDrafts as $d)
                        <input type="hidden" name="ids[]" value="{{ $d->id }}">
                    @endforeach
                    <button type="submit"
                            style="padding:6px 14px;background:#9a5a1a;border:none;border-radius:7px;font-size:12px;font-weight:600;color:#fff;cursor:pointer">
                        Confirm All
                    </button>
                </form>
            </div>
        @endif

        {{-- Expenses table --}}
        <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;overflow:hidden">
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:760px">
                <thead>
                    <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                        <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Title</th>
                        <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Category</th>
                        <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Date</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Amount</th>
                        <th style="text-align:center;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Status</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allExpenses as $expense)
                        <tr style="border-bottom:1px solid #eeeee9"
                            onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background=''">
                            <td style="padding:13px 16px">
                                <div style="font-size:14px;font-weight:500;color:#141413">{{ $expense->title }}</div>
                                @if($expense->recurringCharge)
                                    <div style="font-size:11px;color:#8c8c8a;margin-top:2px">↻ {{ $expense->recurringCharge->name }}</div>
                                @endif
                            </td>
                            <td style="padding:13px 16px">
                                @if($expense->category)
                                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#5c5c5a">
                                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $expense->category->color }};display:inline-block;flex-shrink:0"></span>
                                        {{ $expense->category->name }}
                                    </span>
                                @else
                                    <span style="font-size:12px;color:#c0bfba">—</span>
                                @endif
                            </td>
                            <td style="padding:13px 16px;font-size:13px;color:#5c5c5a">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td style="padding:13px 16px;text-align:right;font-size:14px;font-weight:600;color:#141413">
                                {{ number_format((float) $expense->amount_mru, 2) }} <span style="font-size:11px;color:#8c8c8a">MRU</span>
                                @if(strtoupper((string) $expense->currency) !== 'MRU')
                                    <span style="font-size:11px;color:#8c8c8a">({{ $expense->originalCurrencyLabel() }})</span>
                                @endif
                            </td>
                            <td style="padding:13px 16px;text-align:center">
                                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;{{ $statusStyles[$expense->status] ?? '' }}">
                                    {{ ucfirst($expense->status) }}
                                </span>
                            </td>
                            <td style="padding:13px 16px;text-align:right">
                                <div class="expenses-overview-table-actions" style="display:flex;gap:8px;justify-content:flex-end;align-items:center">
                                    @if($expense->status === 'draft')
                                        <form method="POST" action="{{ route('expenses.confirm', $expense) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    style="padding:5px 12px;background:#edf7f2;border:1px solid #b5deca;border-radius:6px;font-size:12px;font-weight:500;color:#2e7d55;cursor:pointer">
                                                Confirm
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('expenses.edit', $expense) }}"
                                       style="font-size:12px;color:#8c8c8a;text-decoration:none;padding:5px 10px;border:1px solid #e5e4df;border-radius:6px">Edit</a>
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                          onsubmit="return confirm('Delete this expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                style="font-size:12px;color:#b94040;background:none;border:1px solid #f5c6c6;border-radius:6px;padding:5px 10px;cursor:pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:48px;text-align:center;color:#8c8c8a;font-size:14px">
                                No expenses yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($allExpenses->count())
                    <tfoot>
                        <tr style="background:#F5F4EF;border-top:1px solid #e5e4df">
                            <td colspan="3" style="padding:12px 16px;font-size:13px;font-weight:600;color:#5c5c5a">
                                {{ $allExpenses->total() }} expense(s)
                            </td>
                            <td style="padding:12px 16px;text-align:right;font-size:14px;font-weight:700;color:#141413">
                                {{ number_format($allExpenses->getCollection()->sum('amount_mru'), 2) }}
                                <span style="font-size:11px;color:#8c8c8a">MRU</span>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
            </div>
        </div>

        @if($allExpenses->hasPages())
            <div style="margin-top:20px">{{ $allExpenses->links() }}</div>
        @endif

    </div>{{-- /all tab --}}

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- TAB 3: RECURRING CHARGES + MANAGE CATEGORIES --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div x-show="tab === 'recurring'" x-cloak>

        {{-- Recurring charges list --}}
        <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;overflow:hidden;margin-bottom:32px">
            <div style="padding:16px 20px;border-bottom:1px solid #eeeee9;display:flex;align-items:center;justify-content:space-between">
                <h2 style="font-size:15px;font-weight:600;color:#141413;margin:0">Recurring Charges</h2>
            </div>
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:860px">
                <thead>
                    <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                        <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Name</th>
                        <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Category</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Amount</th>
                        <th style="text-align:center;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Frequency</th>
                        <th style="text-align:center;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Next Due</th>
                        <th style="text-align:center;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Status</th>
                        <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allCharges as $charge)
                        <tr style="border-bottom:1px solid #eeeee9"
                            onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background=''">
                            <td style="padding:13px 16px">
                                <div style="font-size:14px;font-weight:500;color:#141413">{{ $charge->name }}</div>
                                @if($charge->project)
                                    <div style="font-size:11px;color:#8c8c8a;margin-top:2px">{{ $charge->project->name }}</div>
                                @endif
                            </td>
                            <td style="padding:13px 16px">
                                @if($charge->category)
                                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#5c5c5a">
                                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $charge->category->color }};display:inline-block"></span>
                                        {{ $charge->category->name }}
                                    </span>
                                @else
                                    <span style="color:#c0bfba;font-size:12px">—</span>
                                @endif
                            </td>
                            <td style="padding:13px 16px;text-align:right;font-size:14px;font-weight:600;color:#141413">
                                {{ number_format($charge->amount, 2) }} <span style="font-size:11px;color:#8c8c8a">MRU</span>
                            </td>
                            <td style="padding:13px 16px;text-align:center">
                                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#F5F4EF;color:#5c5c5a;text-transform:capitalize">
                                    {{ $charge->frequency }}
                                </span>
                            </td>
                            <td style="padding:13px 16px;text-align:center;font-size:13px;color:#5c5c5a">
                                {{ $charge->next_due_date->format('d M Y') }}
                            </td>
                            <td style="padding:13px 16px;text-align:center">
                                {{-- Activate / deactivate toggle --}}
                                <form method="POST" action="{{ route('recurring-charges.toggle', $charge) }}">
                                    @csrf
                                    <button type="submit"
                                            onclick="{{ $charge->is_active ? 'return confirm(\'Stop this recurring charge?\')' : '' }}"
                                            style="font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;cursor:pointer;border:none;
                                                   {{ $charge->is_active ? 'background:#fff0f0;color:#b94040;border:1px solid #ffd0d0' : 'background:#F5F4EF;color:#8c8c8a;border:1px solid #e5e4df' }}">
                                        {{ $charge->is_active ? 'Stop' : 'Resume' }}
                                    </button>
                                </form>
                            </td>
                            <td style="padding:13px 16px;text-align:right">
                                @php
                                    // Find the most recent expense linked to this charge for editing
                                    $linkedExpense = \App\Models\Expense::where('recurring_charge_id', $charge->id)
                                        ->latest('expense_date')->first();
                                @endphp
                                @if($linkedExpense)
                                    <a href="{{ route('expenses.edit', $linkedExpense) }}"
                                       style="font-size:12px;color:#8c8c8a;text-decoration:none;padding:5px 10px;border:1px solid #e5e4df;border-radius:6px">
                                        Edit
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px;text-align:center;color:#8c8c8a;font-size:14px">
                                No recurring charges yet. Create an expense and enable the Recurring toggle.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

    </div>{{-- /recurring tab --}}

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- TAB 4: CATEGORIES --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div x-show="tab === 'categories'" x-cloak>

        <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #eeeee9;display:flex;align-items:center;justify-content:space-between">
                <h2 style="font-size:15px;font-weight:600;color:#141413;margin:0">Expense Categories</h2>
                <span style="font-size:12px;color:#8c8c8a">{{ $allCategories->count() }} categories</span>
            </div>

            {{-- Add new category --}}
            <div style="padding:20px;border-bottom:2px solid #eeeee9;background:#faf9f5">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:12px">New Category</div>
                <form method="POST" action="{{ route('expense-categories.store') }}"
                      style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    @csrf
                    <input type="text" name="name" placeholder="Category name" required
                           style="flex:1;min-width:180px;padding:9px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#fff;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                        <label style="font-size:12px;color:#8c8c8a">Color</label>
                        <input type="color" name="color" value="#D97757" title="Pick a color"
                               style="width:38px;height:38px;border:1px solid #e5e4df;border-radius:8px;cursor:pointer;padding:2px">
                    </div>
                    <button type="submit"
                            style="padding:9px 20px;background:#D97757;border:none;border-radius:8px;font-size:13px;font-weight:500;color:#fff;cursor:pointer;white-space:nowrap">
                        Add Category
                    </button>
                </form>
                @error('name') <p style="margin-top:6px;font-size:12px;color:#b94040">{{ $message }}</p> @enderror
            </div>

            {{-- Category list --}}
            <div>
                @forelse($allCategories as $cat)
                    @php
                        $expenseCount = \App\Models\Expense::where('category_id', $cat->id)->withTrashed()->count();
                    @endphp
                    <div style="display:flex;align-items:center;gap:14px;padding:13px 20px;border-bottom:1px solid #eeeee9"
                         x-data="{ editing: false }">

                        {{-- Color swatch --}}
                        <span style="width:12px;height:12px;border-radius:50%;background:{{ $cat->color }};display:inline-block;flex-shrink:0"></span>

                        {{-- Name (view mode) --}}
                        <span style="font-size:14px;font-weight:500;color:#141413;flex:1" x-show="!editing">
                            {{ $cat->name }}
                            @if($cat->is_system)
                                <span style="font-size:10px;font-weight:600;color:#8c8c8a;background:#F5F4EF;padding:2px 6px;border-radius:4px;margin-left:6px;vertical-align:middle">SYSTEM</span>
                            @endif
                        </span>
                        <span style="font-size:12px;color:#8c8c8a" x-show="!editing">{{ $expenseCount }} expense{{ $expenseCount !== 1 ? 's' : '' }}</span>

                        {{-- Inline edit form --}}
                        <form method="POST" action="{{ route('expense-categories.update', $cat) }}"
                              style="display:flex;align-items:center;gap:8px;flex:1" x-show="editing" x-cloak>
                            @csrf @method('PUT')
                            <input type="text" name="name" value="{{ $cat->name }}" required placeholder="Category name"
                                   style="flex:1;padding:7px 10px;font-size:14px;border:1px solid #D97757;border-radius:7px;background:#fff;color:#141413;outline:none">
                            <input type="color" name="color" value="{{ $cat->color }}" title="Color"
                                   style="width:36px;height:34px;border:1px solid #e5e4df;border-radius:7px;cursor:pointer;padding:2px;flex-shrink:0">
                            <button type="submit"
                                    style="padding:7px 14px;background:#D97757;border:none;border-radius:7px;font-size:12px;font-weight:500;color:#fff;cursor:pointer;white-space:nowrap">
                                Save
                            </button>
                            <button type="button" @click="editing = false"
                                    style="padding:7px 12px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:7px;font-size:12px;color:#141413;cursor:pointer">
                                Cancel
                            </button>
                        </form>

                        {{-- Row actions (view mode) --}}
                        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0" x-show="!editing">
                            <button type="button" @click="editing = true"
                                    style="font-size:12px;color:#8c8c8a;background:#F5F4EF;border:1px solid #e5e4df;border-radius:6px;padding:5px 12px;cursor:pointer;transition:all 150ms ease"
                                    onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">
                                Edit
                            </button>
                            @if(!$cat->is_system && $expenseCount === 0)
                                <form method="POST" action="{{ route('expense-categories.destroy', $cat) }}"
                                      style="display:contents"
                                      onsubmit="return confirm('Delete « {{ addslashes($cat->name) }} »?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="font-size:12px;color:#b94040;background:#fff0f0;border:1px solid #ffd0d0;border-radius:6px;padding:5px 12px;cursor:pointer;transition:all 150ms ease">
                                        Delete
                                    </button>
                                </form>
                            @elseif($cat->is_system)
                                <span style="font-size:11px;color:#8c8c8a;padding:5px 10px;background:#F5F4EF;border-radius:5px" title="System categories cannot be deleted">Protected</span>
                            @else
                                <span style="font-size:11px;color:#b94040;padding:5px 10px;background:#fff0f0;border:1px solid #ffd0d0;border-radius:5px" title="{{ $expenseCount }} expense(s) use this category">In use</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="padding:48px;text-align:center;color:#8c8c8a;font-size:14px">No categories yet. Add one below.</div>
                @endforelse
            </div>
        </div>

    </div>{{-- /categories tab --}}

</div>
</x-layouts.app>
