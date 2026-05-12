<x-filament-widgets::widget>
    <x-filament-actions::modals />

    @php
        // Inline styles — immune to Tailwind purging of dynamic class strings
        $badgeStyles = [
            'background:#eff6ff;color:#1d4ed8',
            'background:#f5f3ff;color:#6d28d9',
            'background:#ecfdf5;color:#047857',
            'background:#fffbeb;color:#b45309',
            'background:#fff1f2;color:#be123c',
            'background:#ecfeff;color:#0e7490',
            'background:#eef2ff;color:#3730a3',
            'background:#f0fdfa;color:#0f766e',
        ];
        $dotStyles = [
            'background:#3b82f6',
            'background:#8b5cf6',
            'background:#10b981',
            'background:#f59e0b',
            'background:#f43f5e',
            'background:#06b6d4',
            'background:#6366f1',
            'background:#14b8a6',
        ];
        $projectColorIdx = [];
        foreach ($this->projects as $idx => $p) {
            $projectColorIdx[$p->id] = $idx % count($badgeStyles);
        }
        $statusStyles = [
            'todo'        => ['dot' => 'background:#9ca3af', 'color' => '#6b7280', 'label' => 'Todo'],
            'in-progress' => ['dot' => 'background:#3b82f6', 'color' => '#2563eb', 'label' => 'In Progress'],
            'in-review'   => ['dot' => 'background:#8b5cf6', 'color' => '#7c3aed', 'label' => 'In Review'],
            'done'        => ['dot' => 'background:#10b981', 'color' => '#059669', 'label' => 'Done'],
            'blocked'     => ['dot' => 'background:#ef4444', 'color' => '#ef4444', 'label' => 'Blocked'],
        ];
        $activeProject = $projectFilter
            ? $this->projects->firstWhere('id', (int) $projectFilter)
            : null;
        $tasks = $this->tasks;
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2 w-full">
                {{-- Title + count --}}
                <span class="font-semibold text-sm">My Tasks</span>
                <span class="text-xs font-medium tabular-nums px-1.5 py-0.5 rounded-md
                             bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    {{ $this->taskCount }}
                </span>

                <div class="ml-auto flex items-center gap-2">
                    {{-- Project filter Alpine dropdown --}}
                    @if ($this->projects->count() > 1)
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                            <button
                                @click="open = !open"
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1.5 rounded-lg transition-colors
                                       {{ $activeProject
                                           ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400'
                                           : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-white/5' }}"
                            >
                                @if ($activeProject)
                                    @php $ci = $projectColorIdx[$activeProject->id] ?? 0; @endphp
                                    <span style="width:6px;height:6px;border-radius:50%;flex-shrink:0;{{ $dotStyles[$ci] }}"></span>
                                    {{ Str::limit($activeProject->name, 18) }}
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                    All projects
                                @endif
                                <svg class="w-3 h-3 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 top-full mt-1.5 z-50 w-52
                                       bg-white dark:bg-gray-800
                                       rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10
                                       py-1 overflow-hidden origin-top-right"
                            >
                                <button wire:click="$set('projectFilter', '')" @click="open=false" type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs transition-colors
                                           {{ $projectFilter === '' ? 'text-primary-600 dark:text-primary-400 bg-primary-50/70 dark:bg-primary-950/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 shrink-0"></span>
                                    <span class="flex-1 text-left font-medium">All projects</span>
                                    @if ($projectFilter === '')
                                        <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    @endif
                                </button>

                                <div class="h-px bg-gray-100 dark:bg-white/5 mx-2 my-1"></div>

                                @foreach ($this->projects as $p)
                                    @php $ci = $projectColorIdx[$p->id] ?? 0; $active = (int)$projectFilter === $p->id; @endphp
                                    <button wire:click="$set('projectFilter', '{{ $p->id }}')" @click="open=false" type="button"
                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-xs transition-colors
                                               {{ $active ? 'text-primary-600 dark:text-primary-400 bg-primary-50/70 dark:bg-primary-950/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5' }}">
                                        <span style="width:6px;height:6px;border-radius:50%;flex-shrink:0;{{ $dotStyles[$ci] }}"></span>
                                        <span class="flex-1 text-left font-medium truncate">{{ $p->name }}</span>
                                        @if ($active)
                                            <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- New task --}}
                    <button wire:click="mountAction('quickAdd')" type="button"
                        class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg
                               bg-primary-600 hover:bg-primary-500 text-white transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        New task
                    </button>
                </div>
            </div>
        </x-slot>

        {{-- ── Empty state ── --}}
        @if ($tasks->isEmpty())
            <div class="flex flex-col items-center gap-2 py-10 text-center">
                <x-filament::icon icon="heroicon-o-check-circle" class="w-10 h-10 text-gray-300 dark:text-gray-600" />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">All caught up!</p>
                @if ($activeProject)
                    <button wire:click="$set('projectFilter', '')"
                        class="text-xs text-primary-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                        Clear filter
                    </button>
                @endif
            </div>

        {{-- ── Task list ── --}}
        @else
            <div class="-mx-6 -mb-6 -mt-6">
                @foreach ($tasks as $task)
                    @php
                        $isDone  = $task->status === 'done';
                        $overdue = $task->due_date && $task->due_date->isPast() && !$isDone;
                        $ss      = $statusStyles[$task->status] ?? $statusStyles['todo'];
                        $ci      = isset($task->project_id) ? ($projectColorIdx[$task->project_id] ?? 0) : 0;
                    @endphp

                    <div wire:key="task-{{ $task->id }}"
                         class="group flex items-center gap-3 px-4 py-2.5
                                border-t border-gray-100 dark:border-white/5
                                hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition-colors">

                        {{-- Checkbox --}}
                        <button
                            wire:click="markDone({{ $task->id }})"
                            wire:loading.attr="disabled"
                            wire:target="markDone({{ $task->id }})"
                            class="shrink-0 w-4 h-4 rounded-full border flex items-center justify-center transition-all
                                   {{ $isDone
                                       ? 'bg-emerald-500 border-emerald-500'
                                       : 'border-gray-300 dark:border-gray-600 hover:border-emerald-400' }}"
                        >
                            <span wire:loading wire:target="markDone({{ $task->id }})">
                                <svg class="animate-spin w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            </span>
                            <span wire:loading.remove wire:target="markDone({{ $task->id }})">
                                <svg class="w-2.5 h-2.5 {{ $isDone ? 'text-white' : 'text-emerald-400 opacity-0 group-hover:opacity-100 transition-opacity' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </button>

                        {{-- Title + optional description (grows) --}}
                        <div class="flex-1 min-w-0">
                            <button
                                wire:click="mountAction('viewTask', { taskId: {{ $task->id }} })"
                                class="block w-full text-left text-sm leading-snug transition-colors truncate
                                       {{ $isDone
                                           ? 'line-through text-gray-400 dark:text-gray-500'
                                           : 'text-gray-800 dark:text-gray-200 hover:text-primary-600 dark:hover:text-primary-400' }}"
                            >{{ $task->title }}</button>

                            @if ($task->description && !$isDone)
                                <p style="font-size:10px;color:#9ca3af;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px;">{{ $task->description }}</p>
                            @endif
                        </div>

                        {{-- Right side: project badge · status · date · avatar — all inline --}}
                        <div class="shrink-0 flex items-center gap-1.5">

                            @if ($task->project)
                                <span style="{{ $badgeStyles[$ci] }};font-size:11px;font-weight:500;padding:2px 6px;border-radius:4px;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;">
                                    <span style="width:5px;height:5px;border-radius:50%;flex-shrink:0;{{ $dotStyles[$ci] }}"></span>
                                    {{ $task->project->name }}
                                </span>
                            @endif

                            <span style="font-size:11px;font-weight:500;color:{{ $ss['color'] }};display:inline-flex;align-items:center;gap:3px;white-space:nowrap;">
                                <span style="width:5px;height:5px;border-radius:50%;flex-shrink:0;{{ $ss['dot'] }}"></span>
                                {{ $ss['label'] }}
                            </span>

                            @if ($overdue)
                                <span style="font-size:11px;font-weight:500;color:#ef4444;white-space:nowrap;">· {{ $task->due_date->format('M j') }}</span>
                            @elseif ($task->due_date)
                                <span style="font-size:11px;color:#9ca3af;white-space:nowrap;">· {{ $task->due_date->format('M j') }}</span>
                            @endif

                            @if ($task->assignee)
                                <span title="{{ $task->assignee->name }}"
                                      style="margin-left:2px;width:20px;height:20px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-size:9px;font-weight:900;display:flex;align-items:center;justify-content:center;text-transform:uppercase;flex-shrink:0;">
                                    {{ substr($task->assignee->name, 0, 1) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($this->taskCount > $tasks->count())
                    <div class="px-4 py-2.5 border-t border-gray-100 dark:border-white/5">
                        <a href="{{ \App\Filament\Resources\TaskResource::getUrl('index') }}"
                           class="text-xs text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                            {{ $this->taskCount - $tasks->count() }} more tasks →
                        </a>
                    </div>
                @endif
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
