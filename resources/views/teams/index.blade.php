@php
    $activeTab = $activeTab ?? 'overview';

    $nextDir = fn (string $column) => $sort === $column && $dir === 'desc' ? 'asc' : 'desc';
    $isSorted = fn (string $column) => $sort === $column;

    $formatDuration = function (?int $seconds): string {
        if ($seconds === null) {
            return '—';
        }

        $minutes = intdiv($seconds, 60);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return $hours.'h '.$mins.'m';
        }

        return max($minutes, 0).'m';
    };
@endphp

<x-layouts.app title="Teams">
    @if($activeTab === 'overview')
        <div class="flex items-center justify-between mb-5">
            <h1 style="font-size:24px;font-weight:600;color:#141413">Teams</h1>
            @if(auth()->user()->hasPermission('teams.manage'))
            <a href="{{ route('teams.create') }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
               onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
                + New Team
            </a>
            @endif
        </div>
    @else
        <div class="mb-5">
            <h1 class="text-[20px] font-semibold text-ink">Team Accountability</h1>
            <p class="text-[13px] text-muted mt-1">
                Range: {{ $rangeStart->format('M d, Y') }} - {{ $rangeEnd->format('M d, Y') }}
            </p>
        </div>
    @endif

    @include('teams._subnav')

    @include('components.flash')

    @if($activeTab === 'overview')
        @if($teams->isEmpty())
            <div class="flex flex-col items-center justify-center py-20" style="color:#8c8c8a">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <p style="font-size:14px;font-weight:500;color:#5c5c5a">No teams yet</p>
                <p style="font-size:13px;margin-top:4px">Create your first team to organize people across projects.</p>
                @if(auth()->user()->hasPermission('teams.manage'))
                <a href="{{ route('teams.create') }}"
                   style="margin-top:16px;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
                   onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
                    + New Team
                </a>
                @endif
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
                @foreach($teams as $team)
                <div x-data="{ confirmDelete: false }"
                     style="background:#fff;border:1px solid #eeeee9;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(20,20,19,0.04);transition:box-shadow 150ms ease,border-color 150ms ease;display:flex;flex-direction:column;gap:12px"
                     onmouseover="this.style.boxShadow='0 4px 14px rgba(20,20,19,0.08)';this.style.borderColor='#e5e4df'"
                     onmouseout="this.style.boxShadow='0 1px 3px rgba(20,20,19,0.04)';this.style.borderColor='#eeeee9'">

                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
                        <div>
                            <a href="{{ route('teams.show', $team) }}"
                               style="font-size:15px;font-weight:600;color:#141413;text-decoration:none;transition:color 150ms ease"
                               onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">
                                {{ $team->name }}
                            </a>
                            @if($team->description)
                            <p style="font-size:13px;color:#5c5c5a;margin-top:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                                {{ $team->description }}
                            </p>
                            @endif
                        </div>
                        @if(auth()->user()->hasPermission('teams.manage'))
                        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
                            <a href="{{ route('teams.edit', $team) }}"
                               style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(0,0,0,0.07);transition:background 120ms ease;color:#5c5c5a"
                               onmouseover="this.style.background='rgba(0,0,0,0.13)';this.style.color='#141413'" onmouseout="this.style.background='rgba(0,0,0,0.07)';this.style.color='#5c5c5a'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <button type="button"
                                    @click="confirmDelete = true"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(0,0,0,0.07);transition:background 120ms ease;border:none;cursor:pointer;color:#5c5c5a"
                                    onmouseover="this.style.background='#fff0f0';this.style.color='#b94040'" onmouseout="this.style.background='rgba(0,0,0,0.07)';this.style.color='#5c5c5a'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            </button>
                        </div>
                        @endif
                    </div>

                    @if($team->lead)
                    <div style="display:flex;align-items:center;gap:6px">
                        <span style="width:22px;height:22px;border-radius:50%;background:{{ $team->lead->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0">
                            {{ $team->lead->initials ?? strtoupper(substr($team->lead->name, 0, 2)) }}
                        </span>
                        <span style="font-size:12px;color:#5c5c5a">{{ $team->lead->name }}</span>
                        <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;background:#F5F4EF;padding:1px 6px;border-radius:4px">Lead</span>
                    </div>
                    @endif

                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="display:flex;align-items:center">
                            @foreach($team->members->take(4) as $i => $member)
                            <span style="width:28px;height:28px;border-radius:50%;background:{{ $member->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;border:2px solid #fff;{{ $i > 0 ? 'margin-left:-8px' : '' }};flex-shrink:0;z-index:{{ 4 - $i }}">
                                {{ $member->initials ?? strtoupper(substr($member->name, 0, 2)) }}
                            </span>
                            @endforeach
                            @if($team->members_count > 4)
                            <span style="width:28px;height:28px;border-radius:50%;background:#F5F4EF;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;color:#8c8c8a;border:2px solid #fff;margin-left:-8px">
                                +{{ $team->members_count - 4 }}
                            </span>
                            @endif
                        </div>
                        <span style="font-size:12px;color:#8c8c8a;font-weight:500">
                            {{ $team->members_count }} {{ $team->members_count === 1 ? 'member' : 'members' }}
                        </span>
                    </div>

                    @if(auth()->user()->hasPermission('teams.manage'))
                    <div x-show="confirmDelete" x-cloak
                         style="border-top:1px solid #ffd0d0;padding-top:12px;margin-top:4px">
                        <p style="font-size:12px;color:#b94040;margin-bottom:8px">Delete <strong>{{ $team->name }}</strong>? This cannot be undone.</p>
                        <div style="display:flex;gap:8px">
                            <button type="button" @click="confirmDelete = false"
                                    style="padding:5px 12px;font-size:12px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:6px;cursor:pointer;color:#141413;transition:background 150ms ease"
                                    onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">
                                Cancel
                            </button>
                            <form method="POST" action="{{ route('teams.destroy', $team) }}" style="margin:0">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        style="padding:5px 12px;font-size:12px;font-weight:500;background:#fff0f0;border:1px solid #ffd0d0;border-radius:6px;cursor:pointer;color:#b94040;transition:background 150ms ease"
                                        onmouseover="this.style.background='#ffe0e0'" onmouseout="this.style.background='#fff0f0'">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="space-y-6">
            <form method="GET" action="{{ route('teams.index') }}" class="flex flex-wrap items-end gap-2">
                <input type="hidden" name="tab" value="accountability">

                <div>
                    <label class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Range</label>
                    <select name="range"
                            class="px-3 py-2 rounded-lg border border-line bg-white text-[13px] text-ink focus:border-accent focus:outline-none">
                        <option value="this_month" @selected($rangePreset === 'this_month')>This month</option>
                        <option value="last_30_days" @selected($rangePreset === 'last_30_days')>Last 30 days</option>
                        <option value="last_3_months" @selected($rangePreset === 'last_3_months')>Last 3 months</option>
                        <option value="custom" @selected($rangePreset === 'custom')>Custom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Start</label>
                    <input type="date" name="start_date" value="{{ request('start_date', $rangeStart->toDateString()) }}"
                           class="px-3 py-2 rounded-lg border border-line bg-white text-[13px] text-ink focus:border-accent focus:outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">End</label>
                    <input type="date" name="end_date" value="{{ request('end_date', $rangeEnd->toDateString()) }}"
                           class="px-3 py-2 rounded-lg border border-line bg-white text-[13px] text-ink focus:border-accent focus:outline-none">
                </div>

                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
                @if(request()->filled('developer_id'))
                    <input type="hidden" name="developer_id" value="{{ request('developer_id') }}">
                @endif

                <button type="submit" class="px-3 py-2 rounded-lg bg-accent text-white text-[13px] font-medium hover:bg-accent-hover transition-colors">
                    Apply
                </button>
            </form>

            <div class="bg-white rounded-xl border border-line overflow-hidden shadow-card">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[13px]">
                        <thead class="bg-canvas border-b border-line">
                            <tr class="text-left text-muted">
                                <th class="px-4 py-3 font-semibold uppercase tracking-wider">Developer</th>
                                @php
                                    $columns = [
                                        'tasks_completed' => 'Tasks completed',
                                        'first_time_right_pct' => 'First-time-right %',
                                        'total_returns' => 'Total returns',
                                        'avg_rounds_per_returned_task' => 'Avg rounds / returned task',
                                        'avg_time_in_progress_seconds' => 'Avg time in progress',
                                        'avg_return_resolution_seconds' => 'Avg return resolve time',
                                        'blocked_transition_attempts' => 'Blocked transition attempts',
                                    ];
                                @endphp
                                @foreach($columns as $columnKey => $label)
                                    <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap">
                                        <a href="{{ route('teams.index', array_merge(request()->query(), ['tab' => 'accountability', 'sort' => $columnKey, 'dir' => $nextDir($columnKey)])) }}"
                                           class="inline-flex items-center gap-1 hover:text-ink transition-colors">
                                            {{ $label }}
                                            @if($isSorted($columnKey))
                                                <span>{{ $dir === 'desc' ? '↓' : '↑' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @forelse($rows as $row)
                                <tr class="hover:bg-canvas/60 transition-colors">
                                    <td class="px-4 py-3 font-medium text-ink whitespace-nowrap">
                                        <a href="{{ route('teams.index', array_merge(request()->query(), ['tab' => 'accountability', 'developer_id' => $row['developer_id']])) }}" class="hover:underline">
                                            {{ $row['name'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $row['tasks_completed'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $row['first_time_right_pct'] !== null ? $row['first_time_right_pct'].'%' : '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-medium text-ink">{{ $row['total_returns'] }}</div>
                                        <div class="text-[11px] text-muted mt-1">
                                            @foreach($row['return_breakdown'] as $reason => $count)
                                                @if($count > 0)
                                                    <span class="inline-flex items-center mr-2">{{ $reason }}: {{ $count }}</span>
                                                @endif
                                            @endforeach
                                            @if($row['total_returns'] === 0)
                                                <span>—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $row['avg_rounds_per_returned_task'] ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $formatDuration($row['avg_time_in_progress_seconds']) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $formatDuration($row['avg_return_resolution_seconds']) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $row['blocked_transition_attempts'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-muted">No developers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($selectedDeveloper)
                <div class="bg-white rounded-xl border border-line overflow-hidden shadow-card">
                    <div class="px-4 py-3 border-b border-line bg-canvas flex items-center justify-between gap-3">
                        <h2 class="text-[14px] font-semibold text-ink">{{ $selectedDeveloper->name }} — Task Drill-down</h2>
                        <a href="{{ route('teams.index', array_merge(request()->except('developer_id'), ['tab' => 'accountability'])) }}" class="text-[12px] font-medium text-muted hover:text-ink transition-colors">Clear</a>
                    </div>

                    @if($drilldownTasks->isEmpty())
                        <div class="px-4 py-8 text-center text-muted text-[13px]">No tasks for this developer in the selected range.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-[13px]">
                                <thead class="bg-canvas border-b border-line text-muted">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider">Task</th>
                                        <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider">Project</th>
                                        <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider">Completed / Status</th>
                                        <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider">Return rounds</th>
                                        <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider">Return categories</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-hairline">
                                    @foreach($drilldownTasks as $task)
                                        <tr>
                                            <td class="px-4 py-2.5 text-ink font-medium">{{ $task['title'] }}</td>
                                            <td class="px-4 py-2.5 text-muted">{{ $task['project'] ?? '—' }}</td>
                                            <td class="px-4 py-2.5">
                                                @if($task['completed_at'])
                                                    {{ \Carbon\Carbon::parse($task['completed_at'])->format('M d, Y H:i') }}
                                                @else
                                                    <span class="text-muted">{{ $task['status'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5">{{ $task['return_rounds'] }}</td>
                                            <td class="px-4 py-2.5 text-muted">
                                                @if(empty($task['return_categories']))
                                                    —
                                                @else
                                                    {{ implode(', ', $task['return_categories']) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</x-layouts.app>
