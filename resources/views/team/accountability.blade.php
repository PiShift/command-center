@php
    /** @var \Carbon\CarbonInterface $rangeStart */
    /** @var \Carbon\CarbonInterface $rangeEnd */

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

<x-layouts.app title="Team Accountability">
    <div class="space-y-6">
        @include('teams._subnav')

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-[20px] font-semibold text-ink">Team Accountability</h1>
                <p class="text-[13px] text-muted mt-1">
                    Range: {{ $rangeStart->format('M d, Y') }} - {{ $rangeEnd->format('M d, Y') }}
                </p>
            </div>

            <form method="GET" class="flex flex-wrap items-end gap-2">
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
        </div>

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
                                    <a href="{{ route('team.accountability', array_merge(request()->query(), ['sort' => $columnKey, 'dir' => $nextDir($columnKey)])) }}"
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
                                    <a href="{{ route('team.accountability', array_merge(request()->query(), ['developer_id' => $row['developer_id']])) }}" class="hover:underline">
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
                    <a href="{{ route('team.accountability', request()->except('developer_id')) }}" class="text-[12px] font-medium text-muted hover:text-ink transition-colors">Clear</a>
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
</x-layouts.app>
