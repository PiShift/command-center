@php
    $statusColors = [
        'in-progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'todo'        => 'bg-slate-100 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300',
        'done'        => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'in-review'   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    ];
    $priorityConfig = [
        'high'   => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',   'High'],
        'medium' => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'Medium'],
        'low'    => ['bg-slate-100 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300', 'Low'],
    ];
    $statusClass   = $statusColors[$task?->status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    [$prioClass, $prioLabel] = $priorityConfig[$task?->priority] ?? ['bg-gray-100 text-gray-500', 'Normal'];
    $overdue = $task?->due_date && $task->due_date->isPast() && $task->status !== 'done';
@endphp

@if (!$task)
    <div class="flex items-center justify-center py-12 text-gray-400 dark:text-gray-600">
        <p class="text-sm">Task not found.</p>
    </div>
@else
    <div class="space-y-5 pb-2">

        {{-- ── Status + Priority badges ─── --}}
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full {{ $statusClass }}">
                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                {{ ucwords(str_replace('-', ' ', $task->status)) }}
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full {{ $prioClass }}">
                {{ $prioLabel }} Priority
            </span>
            @if ($task->type)
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ ucfirst($task->type) }}
                </span>
            @endif
        </div>

        {{-- ── Meta grid ─── --}}
        <div class="grid grid-cols-2 gap-3 text-sm">

            {{-- Project --}}
            @if ($task->project)
                <div class="col-span-2 sm:col-span-1 flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Project</p>
                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ $task->project->name }}</p>
                    </div>
                </div>
            @endif

            {{-- Assignee --}}
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Assignee</p>
                    <p class="font-medium text-gray-800 dark:text-gray-200">
                        {{ $task->assignee?->name ?? '—' }}
                    </p>
                </div>
            </div>

            {{-- Due date --}}
            @if ($task->due_date)
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 {{ $overdue ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Due Date</p>
                        <p class="font-medium {{ $overdue ? 'text-red-500 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' }}">
                            @if ($overdue) ⚠ @endif
                            {{ $task->due_date->format('M j, Y') }}
                            @if ($overdue)
                                <span class="text-red-400 text-xs font-normal ml-1">(overdue)</span>
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            {{-- Created at --}}
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Created</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ $task->created_at->format('M j, Y') }}</p>
                </div>
            </div>
        </div>

        {{-- ── Description ─── --}}
        @if ($task->description)
            <div class="rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60 p-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 mb-2 uppercase tracking-wide">Description</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ $task->description }}</p>
            </div>
        @endif

        {{-- ── Actions ─── --}}
        <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-700/60">
            <a href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $task->id]) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">
                Open full details →
            </a>
            @if ($task->status !== 'done')
                <button
                    wire:click="markDone({{ $task->id }})"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg
                           bg-emerald-50 text-emerald-700 hover:bg-emerald-100
                           dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50
                           transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Mark as done
                </button>
            @endif
        </div>

    </div>
@endif
