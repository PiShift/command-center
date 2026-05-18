<x-layouts.app title="War Room">

{{-- Stats row --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">

    @php
    $stats = [
        ['label' => 'Active Projects', 'value' => $activeProjects,  'desc' => $blockedProjects . ' blocked · ' . $atRiskProjects . ' at risk',  'color' => $blockedProjects > 0 ? 'danger' : ($atRiskProjects > 0 ? 'warn' : 'success')],
        ['label' => 'Open Tasks',      'value' => $openTasks,       'desc' => $inProgressTasks . ' in progress · ' . $highPrioTasks . ' high priority', 'color' => $highPrioTasks > 0 ? 'warn' : 'info'],
        ['label' => 'Overdue Tasks',   'value' => $overdueTasks,    'desc' => $overdueTasks > 0 ? 'Need immediate attention' : 'All tasks on time', 'color' => $overdueTasks > 0 ? 'danger' : 'success'],
        ['label' => 'Done This Week',  'value' => $doneTasks,       'desc' => 'Tasks completed in last 7 days', 'color' => 'success'],
        ['label' => 'Active Customers','value' => $activeCustomers, 'desc' => '', 'color' => 'info'],
    ];
    $colorMap = ['danger' => '#b94040', 'warn' => '#e07b39', 'success' => '#3d9970', 'info' => '#4a90d9'];
    @endphp

    @foreach ($stats as $stat)
    <div class="bg-white border border-line rounded-xl p-4">
        <p class="text-[12px] text-dim font-medium mb-1">{{ $stat['label'] }}</p>
        <p class="text-3xl font-bold text-ink mb-1">{{ $stat['value'] }}</p>
        @if ($stat['desc'])
            <p class="text-[11px]" style="color: {{ $colorMap[$stat['color']] }}">{{ $stat['desc'] }}</p>
        @endif
    </div>
    @endforeach

</div>

{{-- My Tasks --}}
<div class="bg-white border border-line rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-line">
        <h2 class="text-[14px] font-semibold text-ink">My Tasks</h2>
        <a href="{{ route('tasks.create') }}"
           class="inline-flex items-center gap-1.5 text-[12px] font-medium text-accent hover:underline">
            @include('components.icon', ['name' => 'plus'])
            New task
        </a>
    </div>

    @if ($myTasks->isEmpty())
        <div class="px-6 py-10 text-center text-[13px] text-muted">No open tasks assigned to you.</div>
    @else
        <table class="w-full text-[13px]">
            <thead>
                <tr class="text-[11px] font-semibold uppercase tracking-wider text-muted border-b border-hairline">
                    <th class="px-6 py-2 text-left">Task</th>
                    <th class="px-4 py-2 text-left">Project</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Priority</th>
                    <th class="px-4 py-2 text-left">Due</th>
                    <th class="px-4 py-2 text-left"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hairline">
                @foreach ($myTasks as $task)
                <tr class="{{ $task->isOverdue() ? 'bg-red-50' : '' }} hover:bg-hairline">
                    <td class="px-6 py-3 font-medium text-ink">
                        <a href="{{ route('tasks.show', $task) }}" class="hover:text-accent">
                            {{ $task->title }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-dim">{{ $task->project?->name ?? '—' }}</td>
                    <td class="px-4 py-3">@include('components.badge', ['type' => 'status', 'value' => $task->status])</td>
                    <td class="px-4 py-3">@include('components.badge', ['type' => 'priority', 'value' => $task->priority])</td>
                    <td class="px-4 py-3 {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-dim' }}">
                        {{ $task->due_date?->format('M d') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('tasks.advance', $task) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-[11px] text-dim hover:text-accent font-medium">
                                {{ match($task->status) { 'backlog' => 'Start', 'in-progress' => 'Done', default => 'Re-open' } }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

</x-layouts.app>
