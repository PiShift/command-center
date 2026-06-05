<x-layouts.app title="Dashboard">

@php
    $fmt = fn($n) => 'MRU ' . number_format((float)$n, 0, '.', ',');
    $openTasksTotal = ($tasksByStatus['open'] ?? 0) + ($tasksByStatus['todo'] ?? 0) + ($tasksByStatus['in-progress'] ?? 0) + ($tasksByStatus['in-review'] ?? 0);
    $atRiskBlocked  = ($projectsByHealth['at_risk'] ?? 0) + ($projectsByHealth['blocked'] ?? 0);
@endphp

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- Page header                                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-ink">Dashboard</h1>
        <p class="text-[13px] text-muted mt-0.5">
            Showing data for <span class="font-medium text-dim">{{ $periodLabel }}</span>
        </p>
    </div>

    {{-- Date range picker --}}
    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
        <div class="relative">
            <select name="month" onchange="this.form.submit()"
                    class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer"
                    style="background:#F5F4EF; border:1px solid #e5e4df; color:#141413; outline:none">
                @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                @endforeach
            </select>
            <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-muted">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
        </div>
        <div class="relative">
            <select name="year" onchange="this.form.submit()"
                    class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer"
                    style="background:#F5F4EF; border:1px solid #e5e4df; color:#141413; outline:none">
                @foreach(range(now()->year - 2, now()->year) as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-muted">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
        </div>
        <span class="text-[11px] text-muted bg-surface border border-line px-3 py-1.5 rounded-lg whitespace-nowrap">
            Cached 5 min
        </span>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 1 — KPI Cards                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
@php
    $expGrowthCard = $expensesLastMonth > 0 ? round((($expensesThisMonth - $expensesLastMonth) / $expensesLastMonth) * 100, 1) : null;
    $kpiR = 22; $kpiCirc = round(2 * M_PI * $kpiR, 2);
    $kpiOffset = round($kpiCirc * (1 - min(100, max(0, $collectionRate)) / 100), 2);
@endphp

{{-- Row 1: Financial --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">

    {{-- Revenue this month --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)] flex flex-col">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-1">Revenue this month</p>
        <p class="text-lg sm:text-2xl font-bold text-ink leading-none mb-1">{{ $fmt($revenueThisMonth) }}</p>
        @if($revenueLastMonth > 0)
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-semibold {{ $revenueGrowth >= 0 ? 'text-[#3d9970]' : 'text-[#b94040]' }}">
            @if($revenueGrowth >= 0)<svg class="w-3 h-3" viewBox="0 0 12 12" fill="currentColor"><path d="M6 2l4 5H2z"/></svg>
            @else<svg class="w-3 h-3" viewBox="0 0 12 12" fill="currentColor"><path d="M6 10L2 5h8z"/></svg>@endif
            {{ abs($revenueGrowth) }}% vs last month
        </span>
        @else
        <span class="text-[10px] sm:text-[11px] text-muted">First month</span>
        @endif
        <div class="mt-auto -mx-2 pt-2 hidden sm:block">
            <div id="card-sparkline-revenue" style="height:44px"></div>
        </div>
    </div>

    {{-- Outstanding --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-card">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-1">Outstanding</p>
        <p class="text-lg sm:text-2xl font-bold text-ink leading-none mb-1">{{ $fmt($outstandingAmount) }}</p>
        <p class="text-[10px] sm:text-[11px] text-muted mb-1">{{ $outstandingInvoicesCount }} invoice{{ $outstandingInvoicesCount != 1 ? 's' : '' }} unpaid</p>
        @if($overdueInvoicesCount > 0)
        <p class="text-[10px] sm:text-[11px] font-semibold text-[#b94040] truncate sm:whitespace-normal">
            <span class="inline sm:hidden">{{ $overdueInvoicesCount }} overdue</span>
            <span class="hidden sm:inline">{{ $fmt($overdueInvoicesAmount) }} overdue ({{ $overdueInvoicesCount }})</span>
        </p>
        @endif
    </div>

    {{-- Expenses this month --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)] flex flex-col">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-1">Expenses this month</p>
        <p class="text-lg sm:text-2xl font-bold text-ink leading-none mb-1">{{ $fmt($expensesThisMonth) }}</p>
        @if($expGrowthCard !== null)
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-semibold {{ $expGrowthCard <= 0 ? 'text-[#3d9970]' : 'text-[#b94040]' }}">
            @if($expGrowthCard > 0)<svg class="w-3 h-3" viewBox="0 0 12 12" fill="currentColor"><path d="M6 2l4 5H2z"/></svg>
            @else<svg class="w-3 h-3" viewBox="0 0 12 12" fill="currentColor"><path d="M6 10L2 5h8z"/></svg>@endif
            {{ abs($expGrowthCard) }}% vs last month
        </span>
        @else
        <span class="text-[10px] sm:text-[11px] text-muted">First month</span>
        @endif
        <div class="mt-auto -mx-2 pt-2 hidden sm:block">
            <div id="card-sparkline-expenses" style="height:44px"></div>
        </div>
    </div>

    {{-- Net this month --}}
    <div class="border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)] {{ $netThisMonth >= 0 ? 'bg-[#edf7f2]' : 'bg-[#fdf0f0]' }}">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-1">Net this month</p>
        <p class="text-lg sm:text-3xl font-bold leading-none mb-1 {{ $netThisMonth >= 0 ? 'text-[#2e7d55]' : 'text-[#b94040]' }}">{{ $fmt($netThisMonth) }}</p>
        <p class="text-[10px] sm:text-[11px] text-muted">Revenue − Expenses</p>
    </div>

    {{-- Bank accounts summary --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-2">Bank Accounts</p>

        @if($bankAccounts->isEmpty())
            <p class="text-[11px] text-muted">No company accounts configured.</p>
        @else
            <div class="space-y-1.5">
                @foreach($bankAccounts as $account)
                    <div class="flex items-center justify-between gap-2 text-[11px] sm:text-[12px]">
                        <span class="text-dim truncate">{{ $account->name }}</span>
                        <span class="font-semibold {{ $account->computed_balance >= 0 ? 'text-success-text' : 'text-danger' }}">
                            {{ $fmt($account->computed_balance) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

{{-- Row 2: Operational --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Active projects --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-1">Active Projects</p>
        <p class="text-lg sm:text-2xl font-bold text-ink leading-none mb-1">{{ $projectCounts['active'] }}</p>
        <p class="text-[10px] sm:text-[11px] leading-relaxed">
            <span class="text-[#3d9970]">{{ $projectsByHealth['on_track'] }} on track</span>@if($projectsByHealth['at_risk'] > 0) · <span class="text-[#e07b39]">{{ $projectsByHealth['at_risk'] }} at risk</span>@endif@if($projectsByHealth['blocked'] > 0) · <span class="text-[#b94040]">{{ $projectsByHealth['blocked'] }} blocked</span>@endif
        </p>
    </div>

    {{-- Open tasks --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-1">Open Tasks</p>
        <p class="text-lg sm:text-2xl font-bold text-ink leading-none mb-1">{{ $openTasksTotal }}</p>
        @if($overdueTasksCount > 0)
        <p class="text-[10px] sm:text-[11px] font-semibold text-[#b94040]">{{ $overdueTasksCount }} overdue</p>
        @else
        <p class="text-[10px] sm:text-[11px] text-muted">None overdue</p>
        @endif
    </div>

    {{-- Collection rate --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-2">Collection Rate</p>
        <div class="flex items-center gap-2 sm:gap-3">
            <svg width="36" height="36" viewBox="0 0 52 52" class="shrink-0 sm:hidden">
                <circle cx="26" cy="26" r="{{ $kpiR }}" fill="none" stroke="#eeeee9" stroke-width="4"/>
                <circle cx="26" cy="26" r="{{ $kpiR }}" fill="none" stroke="#3d9970" stroke-width="4"
                        stroke-dasharray="{{ $kpiCirc }}" stroke-dashoffset="{{ $kpiOffset }}"
                        stroke-linecap="round" transform="rotate(-90 26 26)"/>
                <text x="26" y="30" text-anchor="middle" font-size="10" font-weight="700" fill="#141413">{{ $collectionRate }}%</text>
            </svg>
            <svg width="52" height="52" viewBox="0 0 52 52" class="shrink-0 hidden sm:block">
                <circle cx="26" cy="26" r="{{ $kpiR }}" fill="none" stroke="#eeeee9" stroke-width="4"/>
                <circle cx="26" cy="26" r="{{ $kpiR }}" fill="none" stroke="#3d9970" stroke-width="4"
                        stroke-dasharray="{{ $kpiCirc }}" stroke-dashoffset="{{ $kpiOffset }}"
                        stroke-linecap="round" transform="rotate(-90 26 26)"/>
                <text x="26" y="30" text-anchor="middle" font-size="10" font-weight="700" fill="#141413">{{ $collectionRate }}%</text>
            </svg>
            <div class="min-w-0">
                <p class="text-lg sm:text-xl font-bold text-ink leading-none">{{ $collectionRate }}%</p>
                <p class="text-[10px] sm:text-[11px] text-muted mt-1">{{ $fmt($totalCollectedThisYear) }} collected</p>
                <p class="text-[10px] text-muted">of {{ $fmt($totalInvoicedThisYear) }} invoiced</p>
            </div>
        </div>
    </div>

    {{-- Available credits --}}
    <div class="bg-white border border-line rounded-xl p-3 sm:p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide text-muted mb-1">Available Credits</p>
        <p class="text-lg sm:text-2xl font-bold text-ink leading-none mb-1">{{ $fmt($availableCreditsSum) }}</p>
        <p class="text-[10px] sm:text-[11px] text-muted">{{ $customersWithCredit }} customer{{ $customersWithCredit != 1 ? 's' : '' }} with credit</p>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 2 — Financial Charts                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">

    {{-- Revenue vs Expenses bar chart --}}
    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline">
            <h2 class="text-[14px] font-semibold text-ink">Revenue vs Expenses</h2>
            <p class="text-[12px] text-muted mt-0.5">Last 6 months · MRU</p>
        </div>
        <div class="p-4">
            <div id="chart-revenue-expenses" style="min-height:240px"></div>
        </div>
    </div>

    {{-- Revenue by customer --}}
    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline">
            <h2 class="text-[14px] font-semibold text-ink">Revenue by Customer</h2>
            <p class="text-[12px] text-muted mt-0.5">Top 5 · this year · MRU</p>
        </div>
        <div class="p-4">
            <div id="chart-revenue-customer" style="min-height:240px"></div>
            @if(count($revenueByCustomer) === 1)
            <p class="text-[11px] text-muted text-center pb-3">All revenue from one customer this period.</p>
            @endif
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 3 — Active Projects Health                                        --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden mb-6">
    <div class="px-4 sm:px-6 py-4 border-b border-hairline flex items-center justify-between">
        <h2 class="text-base sm:text-lg font-semibold text-ink">Active Projects</h2>
        <a href="{{ route('projects.index') }}" class="text-[12px] text-accent hover:underline">View all →</a>
    </div>

    @if(empty($activeProjects))
    <div class="px-6 py-12 text-center text-[13px] text-muted">No active projects.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-[13px]" style="min-width:700px">
            <thead>
                <tr class="bg-canvas text-[11px] font-bold uppercase tracking-wider text-muted border-b border-hairline">
                    <th class="px-6 py-2.5 text-left">Project</th>
                    <th class="px-4 py-2.5 text-left">Customer</th>
                    <th class="px-4 py-2.5 text-left">Active Sprint</th>
                    <th class="px-4 py-2.5 text-left w-36">Progress</th>
                    <th class="px-4 py-2.5 text-left">Tasks</th>
                    <th class="px-4 py-2.5 text-left">Health</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hairline">
                @foreach($activeProjects as $p)
                @php
                    $dot = match($p['health']) {
                        'blocked'  => 'bg-[#b94040]',
                        'at-risk'  => 'bg-[#e07b39]',
                        default    => 'bg-[#3d9970]',
                    };
                    $healthBadgeClass = match($p['health']) {
                        'blocked' => 'bg-[#fdf0f0] text-[#b94040]',
                        'at-risk' => 'bg-[#fef9ec] text-[#9a7a1a]',
                        default   => 'bg-[#edf7f2] text-[#2e7d55]',
                    };
                    $healthLabel = match($p['health']) {
                        'blocked' => 'Blocked',
                        'at-risk' => 'At Risk',
                        default   => 'On Track',
                    };
                @endphp
                <tr class="hover:bg-canvas transition-colors">
                    <td class="px-6 py-3">
                        <a href="{{ route('projects.show', $p['id']) }}" class="font-medium text-ink hover:text-accent transition-colors flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full shrink-0 {{ $dot }}"></span>
                            {{ $p['name'] }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $p['customer_name'] ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($p['sprint_name'])
                        <div class="text-[13px] text-dim">{{ $p['sprint_name'] }}</div>
                        @if($p['sprint_deadline'])
                        @php $dl = is_string($p['sprint_deadline']) ? \Carbon\Carbon::parse($p['sprint_deadline']) : $p['sprint_deadline']; @endphp
                        <div class="text-[11px] {{ ($p['sprint_days_left'] ?? 99) <= 3 ? 'text-[#b94040]' : 'text-muted' }}">
                            {{ $dl->format('M j') }}
                            @if(($p['sprint_days_left'] ?? null) !== null)
                                · {{ $p['sprint_days_left'] >= 0 ? $p['sprint_days_left'] . 'd left' : abs($p['sprint_days_left']) . 'd overdue' }}
                            @endif
                        </div>
                        @endif
                        @else
                        <span class="text-muted text-[12px]">No active sprint</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-hairline rounded-full overflow-hidden">
                                <div class="h-full bg-[#3d9970] rounded-full" style="width: {{ $p['progress_pct'] }}%"></div>
                            </div>
                            <span class="text-[11px] text-muted shrink-0">{{ $p['progress_pct'] }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-surface text-muted">{{ $p['open'] }} open</span>
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-[#fdf3ee] text-[#D97757]">{{ $p['in_progress'] }} active</span>
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-[#edf7f2] text-[#2e7d55]">{{ $p['done'] }} done</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $healthBadgeClass }}">{{ $healthLabel }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Health summary row --}}
    <div class="px-6 py-4 border-t border-hairline bg-canvas grid grid-cols-3 gap-4">
        <div class="text-center">
            <p class="text-xl font-bold text-[#3d9970]">{{ $projectsByHealth['on_track'] }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted mt-0.5">On Track</p>
        </div>
        <div class="text-center border-x border-hairline">
            <p class="text-xl font-bold text-[#e07b39]">{{ $projectsByHealth['at_risk'] }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted mt-0.5">At Risk</p>
        </div>
        <div class="text-center">
            <p class="text-xl font-bold text-[#b94040]">{{ $projectsByHealth['blocked'] }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted mt-0.5">Blocked</p>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 4 — Tasks Overview                                                --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-6">

    {{-- Task pipeline donut --}}
    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline">
            <h2 class="text-[14px] font-semibold text-ink">Task Pipeline</h2>
            <p class="text-[12px] text-muted mt-0.5">By status · all projects</p>
        </div>
        <div class="p-4">
            <div id="chart-task-pipeline" style="min-height:250px"></div>
            @if($tasksOpenCount > 0)
            <p class="text-[11px] text-muted text-center -mt-1 pb-1">
                <a href="{{ route('tasks.index', ['status' => 'open']) }}" class="hover:underline text-accent">{{ $tasksOpenCount }} unclaimed task{{ $tasksOpenCount != 1 ? 's' : '' }}</a> not in pipeline
            </p>
            @endif
        </div>
    </div>

    {{-- Priority & Type --}}
    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline">
            <h2 class="text-[14px] font-semibold text-ink">Priority & Type</h2>
            <p class="text-[12px] text-muted mt-0.5">Open tasks only</p>
        </div>
        <div class="p-5 space-y-5">
            {{-- Priority bars --}}
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-muted mb-2">By Priority</p>
                @php $maxPri = max(array_values($tasksByPriority) ?: [1]); @endphp
                @foreach([['high','#b94040'],['medium','#9a7a1a'],['low','#2e7d55']] as [$pri, $tc])
                @php $cnt = $tasksByPriority[$pri] ?? 0; $pct = $maxPri > 0 ? round($cnt/$maxPri*100) : 0; @endphp
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-14 text-[11px] font-semibold capitalize" style="color:{{ $tc }}">{{ $pri }}</span>
                    <div class="flex-1 h-2 bg-hairline rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width:{{ $pct }}%; background:{{ $tc }};"></div>
                    </div>
                    <span class="w-6 text-right text-[11px] text-muted">{{ $cnt }}</span>
                </div>
                @endforeach
            </div>
            {{-- Type bars --}}
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-muted mb-2">By Type</p>
                @php $maxType = max(array_values($tasksByType) ?: [1]); @endphp
                @foreach([['feature','#4a90d9'],['bug','#b94040'],['change','#7c5cbf']] as [$type, $tc])
                @php $cnt = $tasksByType[$type] ?? 0; $pct = $maxType > 0 ? round($cnt/$maxType*100) : 0; @endphp
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-14 text-[11px] font-semibold capitalize" style="color:{{ $tc }}">{{ $type }}</span>
                    <div class="flex-1 h-2 bg-hairline rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width:{{ $pct }}%; background:{{ $tc }};"></div>
                    </div>
                    <span class="w-6 text-right text-[11px] text-muted">{{ $cnt }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Completion sparkline --}}
    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline">
            <h2 class="text-[14px] font-semibold text-ink">Tasks Completed</h2>
            <p class="text-[12px] text-muted mt-0.5">Last 30 days</p>
        </div>
        <div class="px-5 py-3">
            <p class="text-3xl font-bold text-ink">{{ array_sum($tasksCompletedFilled) }}</p>
            <p class="text-[12px] text-muted">tasks done this period</p>
        </div>
        @if(array_sum($tasksCompletedFilled) > 0)
        <div id="chart-sparkline" style="min-height:120px"></div>
        @else
        <div class="flex items-center justify-center" style="min-height:120px">
            <p class="text-[13px] text-muted">No completed tasks this period</p>
        </div>
        @endif
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 5 — Alerts & Deadlines                                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">

    {{-- Overdue tasks --}}
    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline flex items-center gap-2">
            <h2 class="text-[14px] font-semibold text-ink">Overdue Tasks</h2>
            @if($overdueTasksCount > 0)
            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#fdf0f0] text-[#b94040]">{{ $overdueTasksCount }}</span>
            @endif
        </div>
        @if($overdueTasksCount === 0)
        <div class="px-5 py-10 text-center">
            <p class="text-[14px] font-medium text-[#3d9970]">✓ All tasks on time</p>
        </div>
        @else
        <div class="divide-y divide-hairline">
            @foreach($overdueTasksList as $t)
            <div class="px-5 py-3 flex items-center justify-between hover:bg-canvas transition-colors">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('tasks.show', $t['id']) }}" class="text-[13px] font-medium text-ink hover:text-accent transition-colors block truncate">
                        {{ $t['title'] }}
                    </a>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[11px] text-muted">{{ $t['project_name'] }}</span>
                        @if($t['assignee'])
                        <span class="text-[11px] text-muted">· {{ $t['assignee'] }}</span>
                        @endif
                    </div>
                </div>
                <span class="ml-3 shrink-0 text-[11px] font-semibold text-[#b94040]">{{ $t['days_overdue'] }}d overdue</span>
            </div>
            @endforeach
        </div>
        <div class="px-5 py-3 border-t border-hairline bg-canvas">
            <a href="{{ route('tasks.index', ['overdue' => 1]) }}" class="text-[12px] text-accent hover:underline">View all overdue tasks →</a>
        </div>
        @endif
    </div>

    {{-- Sprint deadlines --}}
    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline flex items-center gap-2">
            <h2 class="text-[14px] font-semibold text-ink">Upcoming Sprint Deadlines</h2>
            @if(count($sprintDeadlines) > 0)
            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-surface text-muted">{{ count($sprintDeadlines) }}</span>
            @endif
        </div>
        @if(empty($sprintDeadlines))
        <div class="px-5 py-10 text-center">
            <p class="text-[13px] text-muted">No sprints ending in the next 14 days.</p>
        </div>
        @else
        <div class="divide-y divide-hairline">
            @foreach($sprintDeadlines as $s)
            @php
                $dayColor = $s['days_left'] < 3 ? 'text-[#b94040]' : ($s['days_left'] < 7 ? 'text-[#e07b39]' : 'text-[#3d9970]');
                $dl = is_string($s['deadline']) ? \Carbon\Carbon::parse($s['deadline']) : $s['deadline'];
            @endphp
            <div class="px-5 py-3 hover:bg-canvas transition-colors">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-medium text-ink truncate">{{ $s['sprint_name'] }}</p>
                        <p class="text-[11px] text-muted">{{ $s['project_name'] }}</p>
                    </div>
                    <div class="ml-3 text-right shrink-0">
                        <p class="text-[12px] font-semibold {{ $dayColor }}">{{ $s['days_left'] }}d left</p>
                        <p class="text-[11px] text-muted">{{ $dl->format('M j') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 bg-hairline rounded-full overflow-hidden">
                        <div class="h-full bg-[#4a90d9] rounded-full" style="width:{{ $s['progress_pct'] }}%"></div>
                    </div>
                    <span class="text-[11px] text-muted shrink-0">{{ $s['progress_pct'] }}%</span>
                    <a href="{{ route('projects.show', $s['project_id']) }}" class="text-[11px] text-accent hover:underline shrink-0">View →</a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 6 — Team Performance                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden mb-6">
    <div class="px-4 sm:px-6 py-4 border-b border-hairline">
        <h2 class="text-base sm:text-lg font-semibold text-ink">Team Performance — This Month</h2>
    </div>

    {{-- Top performers --}}
    @if(!empty($topPerformers) && collect($topPerformers)->sum('completed_month') > 0)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 p-4 sm:p-5 border-b border-hairline">
        @foreach($topPerformers as $i => $p)
        <div class="bg-canvas border border-line rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-[15px] font-bold shrink-0"
                 style="background: {{ $p['color'] }}">
                {{ $p['initials'] }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-semibold text-ink truncate">{{ $p['name'] }}</p>
                <p class="text-2xl font-bold text-ink leading-none mt-0.5">{{ $p['completed_month'] }}</p>
                <p class="text-[11px] text-muted">tasks done · {{ $p['in_progress'] }} in progress</p>
            </div>
            @if($i === 0)<span class="ml-auto text-xl shrink-0">🥇</span>
            @elseif($i === 1)<span class="ml-auto text-xl shrink-0">🥈</span>
            @else<span class="ml-auto text-xl shrink-0">🥉</span>
            @endif
        </div>
        @endforeach
    </div>
    @elseif(!empty($topPerformers))
    <div class="px-6 py-5 border-b border-hairline">
        <p class="text-[13px] text-muted text-center">No completed tasks recorded this month yet.</p>
    </div>
    @endif

    {{-- Full team table --}}
    @if(!empty($teamPerformance))
    @php $maxActive = max(array_column($teamPerformance, 'active_total') ?: [1]); @endphp
    <div class="overflow-x-auto">
        <table class="w-full text-[13px]" style="min-width:600px">
            <thead>
                <tr class="bg-canvas text-[11px] font-bold uppercase tracking-wider text-muted border-b border-hairline">
                    <th class="px-6 py-2.5 text-left">Developer</th>
                    <th class="px-4 py-2.5 text-center">Completed this month</th>
                    <th class="px-4 py-2.5 text-center">Active now</th>
                    <th class="px-4 py-2.5 text-center">Total assigned</th>
                    <th class="px-4 py-2.5 text-center">
                        <span class="inline-flex items-center gap-1">
                            Points completed
                            <span title="Sum of task weights completed this month (1=trivial, 5=complex)." class="cursor-help text-muted border border-line rounded-full w-3.5 h-3.5 inline-flex items-center justify-center text-[9px] leading-none">?</span>
                        </span>
                    </th>
                    <th class="px-6 py-2.5 text-left w-40">Workload</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hairline">
                @foreach($teamPerformance as $m)
                @php $loadPct = $maxActive > 0 ? round($m['active_total'] / $maxActive * 100) : 0; @endphp
                <tr class="hover:bg-canvas transition-colors">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[11px] font-bold shrink-0"
                                 style="background: {{ $m['color'] }}">{{ $m['initials'] }}</div>
                            <span class="font-medium text-ink">{{ $m['name'] }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-[#3d9970]">{{ $m['completed_month'] }}</td>
                    <td class="px-4 py-3 text-center text-[#D97757] font-medium">{{ $m['in_progress'] }}</td>
                    <td class="px-4 py-3 text-center text-dim">{{ $m['active_total'] }}</td>
                    <td class="px-4 py-3 text-center text-muted">{{ $m['weight_completed'] }}</td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-hairline rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ $loadPct }}%; background: {{ $m['color'] }};"></div>
                            </div>
                            <span class="text-[11px] text-muted w-8 text-right">{{ $m['active_total'] }}</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Workload chart --}}
    @if(!empty($teamWorkload))
    <div class="p-5 border-t border-hairline">
        <p class="text-[12px] font-semibold text-muted uppercase tracking-wide mb-3">Workload Distribution</p>
        <div id="chart-workload" class="w-full overflow-hidden" style="min-height:{{ max(80, count($teamWorkload) * 36) }}px"></div>
    </div>
    @endif
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 7 — Recent Activity                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden mb-6">
    <div class="px-4 sm:px-6 py-4 border-b border-hairline">
        <h2 class="text-base sm:text-lg font-semibold text-ink">Recent Activity</h2>
    </div>
    @if(empty($recentActivity))
    <div class="px-6 py-10 text-center text-[13px] text-muted">No recent activity.</div>
    @else
    <div class="divide-y divide-hairline">
        @foreach($recentActivity as $act)
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-3 hover:bg-canvas transition-colors">
            <div class="flex items-start gap-3 flex-1 min-w-0">
                <div class="w-1.5 h-1.5 rounded-full bg-accent mt-2 shrink-0"></div>
                <div class="min-w-0 flex-1">
                    @php
                        $actDesc = $act['description'];
                        $actTitle = $act['subject_title'];
                        $actProject = $act['project_name'];
                    @endphp
                    <p class="text-[13px] text-dim">
                        <span class="font-semibold text-ink">{{ $act['causer_name'] }}</span>
                        @if(str_contains($actDesc, 'claimed') && $actTitle)
                            claimed <span class="font-medium text-dim">{{ $actTitle }}</span>@if($actProject) <span class="text-muted">· {{ $actProject }}</span>@endif
                        @elseif(str_contains($actDesc, 'status changed') && $actTitle)
                            {{ $actDesc }} <span class="font-medium text-dim">{{ $actTitle }}</span>@if($actProject) <span class="text-muted">· {{ $actProject }}</span>@endif
                        @elseif(str_contains($actDesc, 'comment') && $actTitle)
                            commented on <span class="font-medium text-dim">{{ $actTitle }}</span>@if($actProject) <span class="text-muted">· {{ $actProject }}</span>@endif
                        @else
                            {{ $actDesc }}@if($actTitle) — <span class="font-medium text-dim">{{ $actTitle }}</span>@if($actProject) <span class="text-muted">· {{ $actProject }}</span>@endif@endif
                        @endif
                    </p>
                </div>
            </div>
            <span class="text-[11px] text-muted shrink-0 sm:mt-0.5 pl-4 sm:pl-0">{{ $act['time_ago'] }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- Charts — ApexCharts                                                       --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
    const revenueByMonth  = @json(array_values($revenueByMonth));
    const expensesByMonth = @json(array_values($expensesByMonth));
    const monthLabels     = @json(array_keys($revenueByMonth));

    const revenueByCustomer = @json($revenueByCustomer);
    const customerNames     = Object.keys(revenueByCustomer);
    const customerRevenues  = Object.values(revenueByCustomer);

    const tasksPipeline   = @json($tasksPipeline);
    const pipelineLabels  = ['To Do', 'In Progress', 'In Review'];
    const pipelineColors  = ['#8c8c8a', '#4a90d9', '#e07b39'];
    const pipelineValues  = [tasksPipeline['todo'] ?? 0, tasksPipeline['in-progress'] ?? 0, tasksPipeline['in-review'] ?? 0];

    const sparkValues = @json(array_values($tasksCompletedFilled));
    const sparkDays   = @json(array_keys($tasksCompletedFilled));

    const workloadData = @json($teamWorkload);

    const FONT    = "'Inter', ui-sans-serif, system-ui, sans-serif";
    const TOOLBAR = { show: false };

    // ── Card sparkline — Revenue ──────────────────────────────────────────────
    new ApexCharts(document.getElementById('card-sparkline-revenue'), {
        chart: { type: 'area', height: 44, fontFamily: FONT, toolbar: TOOLBAR, sparkline: { enabled: true }, animations: { enabled: false } },
        series: [{ name: 'Revenue', data: revenueByMonth }],
        colors: ['#3d9970'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.0 } },
        tooltip: { x: { show: false }, y: { formatter: v => 'MRU ' + Math.round(v).toLocaleString() } },
    }).render();

    // ── Card sparkline — Expenses ─────────────────────────────────────────────
    new ApexCharts(document.getElementById('card-sparkline-expenses'), {
        chart: { type: 'area', height: 44, fontFamily: FONT, toolbar: TOOLBAR, sparkline: { enabled: true }, animations: { enabled: false } },
        series: [{ name: 'Expenses', data: expensesByMonth }],
        colors: ['#b94040'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.0 } },
        tooltip: { x: { show: false }, y: { formatter: v => 'MRU ' + Math.round(v).toLocaleString() } },
    }).render();

    // ── Revenue vs Expenses ───────────────────────────────────────────────────
    new ApexCharts(document.getElementById('chart-revenue-expenses'), {
        chart: { type: 'bar', height: 240, width: '100%', fontFamily: FONT, toolbar: TOOLBAR, animations: { enabled: true, speed: 600 } },
        series: [
            { name: 'Revenue',  data: revenueByMonth },
            { name: 'Expenses', data: expensesByMonth },
        ],
        colors: ['#3d9970', '#b94040'],
        xaxis: { categories: monthLabels, labels: { style: { colors: '#8c8c8a', fontSize: '11px' } } },
        yaxis: { labels: { formatter: v => 'MRU ' + Math.round(v).toLocaleString(), style: { colors: '#8c8c8a', fontSize: '11px' } } },
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        legend: { position: 'top', fontSize: '12px', fontFamily: FONT, labels: { colors: '#5c5c5a' } },
        tooltip: { y: { formatter: v => 'MRU ' + v.toLocaleString() } },
        grid: { borderColor: '#eeeee9' },
        responsive: [{ breakpoint: 640, options: { chart: { height: 200 } } }],
    }).render();

    // ── Revenue by Customer ───────────────────────────────────────────────────
    new ApexCharts(document.getElementById('chart-revenue-customer'), {
        chart: { type: 'bar', height: 240, width: '100%', fontFamily: FONT, toolbar: TOOLBAR, animations: { enabled: true, speed: 600 } },
        series: [{ name: 'Revenue', data: customerRevenues }],
        colors: ['#4a90d9', '#3d9970', '#e07b39', '#7c5cbf', '#D97757'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true, barHeight: '60%' } },
        xaxis: { categories: customerNames, labels: { style: { colors: '#8c8c8a', fontSize: '11px' } } },
        yaxis: { labels: { style: { colors: '#5c5c5a', fontSize: '12px' } } },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { y: { formatter: v => 'MRU ' + v.toLocaleString() } },
        grid: { borderColor: '#eeeee9' },
        responsive: [{ breakpoint: 640, options: { chart: { height: 200 } } }],
    }).render();

    // ── Task Pipeline Donut ───────────────────────────────────────────────────
    new ApexCharts(document.getElementById('chart-task-pipeline'), {
        chart: { type: 'donut', height: 250, width: '100%', fontFamily: FONT, animations: { enabled: true, speed: 600 } },
        series: pipelineValues,
        labels: pipelineLabels,
        colors: pipelineColors,
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '12px', fontFamily: FONT, labels: { colors: '#5c5c5a' } },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true, label: 'In Pipeline', color: '#8c8c8a',
                            fontSize: '12px', fontWeight: 600,
                            formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                        }
                    }
                }
            }
        },
        tooltip: { y: { formatter: v => v + ' tasks' } },
        responsive: [{ breakpoint: 640, options: { chart: { height: 200 } } }],
    }).render();

    // ── Completion Sparkline ──────────────────────────────────────────────────
    const sparkEl = document.getElementById('chart-sparkline');
    if (sparkEl) {
        new ApexCharts(sparkEl, {
            chart: { type: 'area', height: 120, fontFamily: FONT, toolbar: TOOLBAR, sparkline: { enabled: true }, animations: { enabled: true, speed: 600 } },
            series: [{ name: 'Tasks done', data: sparkValues }],
            colors: ['#3d9970'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
            xaxis: { categories: sparkDays },
            tooltip: { x: { show: true }, y: { formatter: v => v + ' tasks' } },
        }).render();
    }

    // ── Workload Distribution ─────────────────────────────────────────────────
    const wlEl = document.getElementById('chart-workload');
    if (wlEl && workloadData.length > 0) {
        new ApexCharts(wlEl, {
            chart: {
                type: 'bar',
                height: Math.max(80, workloadData.length * 36),
                width: '100%',
                fontFamily: FONT, toolbar: TOOLBAR,
                animations: { enabled: true, speed: 600 }
            },
            responsive: [{ breakpoint: 640, options: { chart: { height: 200 } } }],
            series: [{
                name: 'Active tasks',
                data: workloadData.map(m => ({ x: m.name, y: m.active_tasks, fillColor: m.color }))
            }],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true, barHeight: '55%' } },
            dataLabels: { enabled: false },
            legend: { show: false },
            xaxis: { labels: { style: { colors: '#8c8c8a', fontSize: '11px' } } },
            yaxis: { labels: { style: { colors: '#5c5c5a', fontSize: '12px' } } },
            tooltip: { y: { formatter: v => v + ' active tasks' } },
            grid: { borderColor: '#eeeee9' },
        }).render();
    }
</script>
@endpush

</x-layouts.app>
