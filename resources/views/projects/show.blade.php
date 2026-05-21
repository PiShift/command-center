<x-layouts.app :title="$project->name">

<div class="max-w-6xl mx-auto space-y-6"
     x-data="{
         tab: (() => {
             const h = window.location.hash.replace('#', '');
             return ['overview','sprints','backlog','guide'].includes(h) ? h : 'overview';
         })(),
         setTab(t) {
             this.tab = t;
             window.location.hash = '#' + t;
         }
     }"
     x-on:switch-tab.window="setTab($event.detail.tab)">

    @include('components.flash')

    {{-- ── Header ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <div class="flex">
            <div class="w-1 flex-shrink-0" style="background: {{ $project->color ?? '#D97757' }}"></div>
            <div class="flex-1 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-[22px] font-bold text-ink leading-tight">{{ $project->name }}</h1>
                            @include('components.badge', ['type' => 'project_status', 'value' => $project->status])
                            @include('components.badge', ['type' => 'health', 'value' => $project->health ?? 'on-track'])
                        </div>
                        <div class="flex items-center gap-4 flex-wrap">
                            @if($project->customer && auth()->user()->can('customers.view'))
                            <span class="text-[13px] text-dim">
                                <span class="text-muted">Customer:</span>
                                <a href="{{ route('customers.show', $project->customer) }}" class="hover:text-accent transition-colors">{{ $project->customer->name }}</a>
                            </span>
                            @endif
                            @if($project->github_repo)
                            <a href="https://github.com/{{ $project->github_repo }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 text-[12px] text-dim hover:text-accent transition-colors">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                                {{ $project->github_repo }}
                            </a>
                            @endif
                            @if($project->stack)
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium text-muted bg-surface border border-hairline rounded-md">{{ $project->stack }}</span>
                            @endif
                        </div>
                        @if($project->description)
                        <p class="text-[13px] text-dim max-w-2xl">{{ $project->description }}</p>
                        @endif
                    </div>
                    @if($canManage)
                    <a href="{{ route('projects.edit', $project) }}"
                       class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors">
                        @include('components.icon', ['name' => 'pencil'])
                        Edit
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tab bar ──────────────────────────────────────────────────────────── --}}
    <div class="bg-white border border-line rounded-xl p-1 inline-flex gap-0.5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <button @click="setTab('overview')"
                :class="tab === 'overview' ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]' : 'text-muted hover:text-dim'"
                class="px-4 py-1.5 text-[13px] font-medium rounded-lg transition-colors duration-150 cursor-pointer">
            Overview
        </button>
        @if($canManage)
        <button @click="setTab('sprints')"
                :class="tab === 'sprints' ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]' : 'text-muted hover:text-dim'"
                class="px-4 py-1.5 text-[13px] font-medium rounded-lg transition-colors duration-150 cursor-pointer">
            Sprints & Tasks
        </button>
        <button @click="setTab('backlog')"
                :class="tab === 'backlog' ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]' : 'text-muted hover:text-dim'"
                class="px-4 py-1.5 text-[13px] font-medium rounded-lg transition-colors duration-150 cursor-pointer">
            Backlog
        </button>
        @endif
        <button @click="setTab('guide')"
                :class="tab === 'guide' ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]' : 'text-muted hover:text-dim'"
                class="px-4 py-1.5 text-[13px] font-medium rounded-lg transition-colors duration-150 cursor-pointer">
            Guide
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Overview tab                                                          --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'overview'" class="space-y-6">

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @if($canManage)
            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Total Tasks</p>
                <p class="text-[28px] font-bold text-ink leading-none">{{ $totalTasks }}</p>
            </div>
            @endif
            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-1">{{ $canManage ? 'Open' : 'My Open' }}</p>
                <p class="text-[28px] font-bold text-ink leading-none">{{ $canManage ? $openTasks : $myTasks->count() }}</p>
            </div>
            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Done</p>
                <p class="text-[28px] font-bold text-[#2e7d55] leading-none">{{ $doneTasks }}</p>
            </div>
            @if($canManage)
            <div class="rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)] border {{ $overdueTasks > 0 ? 'bg-[#fff8f8] border-[#ffd0d0]' : 'bg-white border-line' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider mb-1 {{ $overdueTasks > 0 ? 'text-[#b94040]' : 'text-muted' }}">Overdue</p>
                <p class="text-[28px] font-bold leading-none {{ $overdueTasks > 0 ? 'text-[#b94040]' : 'text-ink' }}">{{ $overdueTasks }}</p>
            </div>
            @else
            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-1">To Claim</p>
                <p class="text-[28px] font-bold {{ $availableTasks->count() > 0 ? 'text-[#2e7d55]' : 'text-ink' }} leading-none">{{ $availableTasks->count() }}</p>
            </div>
            @endif
        </div>

        {{-- Progress bar --}}
        <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[12px] font-semibold text-dim uppercase tracking-wider">Overall Progress</span>
                <span class="text-[13px] font-bold text-ink">{{ $progressPercent }}%</span>
            </div>
            <div class="w-full h-2 bg-surface rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-300"
                     style="width: {{ $progressPercent }}%; background: {{ $project->color ?? '#D97757' }}"></div>
            </div>
            @if($project->start_date || $project->deadline)
            <div class="flex items-center justify-between mt-3 text-[12px] text-muted">
                <span>{{ $project->start_date?->format('M d, Y') ?? '—' }}</span>
                @php $isOverdue = $project->isOverdue(); @endphp
                <span class="{{ $isOverdue ? 'text-[#b94040] font-semibold' : '' }}">
                    {{ $project->deadline?->format('M d, Y') ?? '—' }}
                    @if($isOverdue) · Overdue @endif
                </span>
            </div>
            @endif
        </div>

        {{-- Two-column layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

            {{-- Left col (3/5) --}}
            <div class="lg:col-span-3 space-y-4">

                @if($canManage)
                {{-- Recent Tasks (managers) --}}
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                        <h2 class="text-[14px] font-semibold text-ink">Recent Tasks</h2>
                        @if(auth()->user()->hasPermission('tasks.create'))
                        <a href="{{ route('tasks.create') . '?project_id=' . $project->id }}"
                           class="inline-flex items-center gap-1 text-[12px] font-medium text-accent hover:underline">
                            @include('components.icon', ['name' => 'plus'])
                            Add task
                        </a>
                        @endif
                    </div>
                    @if($recentTasks->isEmpty())
                    <div class="px-6 py-10 flex flex-col items-center text-center">
                        <svg class="w-10 h-10 text-muted mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                        </svg>
                        <p class="text-[14px] font-medium text-dim">No tasks yet</p>
                        <p class="text-[13px] text-muted mt-1">Add the first task to get started.</p>
                    </div>
                    @else
                    <ul class="divide-y divide-hairline">
                        @foreach($recentTasks as $task)
                        <li class="flex items-center gap-4 px-6 py-3 hover:bg-canvas transition-colors">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('tasks.show', $task) }}"
                                   class="text-[13.5px] font-medium text-ink hover:text-accent transition-colors truncate block">{{ $task->title }}</a>
                                @if($task->due_date)
                                <p class="text-[12px] {{ $task->isOverdue() ? 'text-[#b94040] font-medium' : 'text-muted' }} mt-0.5">
                                    Due {{ $task->due_date->format('M d, Y') }}
                                </p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                @include('components.badge', ['type' => 'status', 'value' => $task->status])
                                @if($task->assignee)
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-semibold text-white flex-shrink-0"
                                      style="background: {{ $task->assignee->color ?? '#8c8c8a' }}"
                                      title="{{ $task->assignee->name }}">
                                    {{ $task->assignee->initials ?? mb_strtoupper(mb_substr($task->assignee->name, 0, 2)) }}
                                </span>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @if($project->tasks->count() > 8)
                    <div class="px-6 py-3 border-t border-hairline">
                        <a href="{{ route('tasks.index', ['project' => $project->id]) }}"
                           class="text-[12px] font-medium text-accent hover:underline">
                            View all {{ $project->tasks->count() }} tasks &rarr;
                        </a>
                    </div>
                    @endif
                    @endif
                </div>
                @else
                {{-- Developer: Available to Claim --}}
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="px-6 py-4 border-b border-hairline">
                        <h2 class="text-[14px] font-semibold text-ink">Available to Claim</h2>
                        <p class="text-[12px] text-muted mt-0.5">Open, unassigned tasks in the active sprint</p>
                    </div>
                    @if($availableTasks->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <p class="text-[14px] font-medium text-dim">No tasks to claim right now</p>
                        <p class="text-[13px] text-muted mt-1">Check back when the sprint has open tasks.</p>
                    </div>
                    @else
                    <ul class="divide-y divide-hairline">
                        @foreach($availableTasks as $task)
                        <li class="flex items-center gap-3 px-6 py-3.5 hover:bg-canvas transition-colors">
                            @if($task->weight)
                            <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded text-[10px] font-bold bg-surface text-muted border border-hairline" title="Complexity weight">{{ $task->weight }}</span>
                            @else
                            <span class="flex-shrink-0 w-6"></span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <button type="button"
                                        onclick="window.dispatchEvent(new CustomEvent('open-task', { detail: { id: {{ $task->id }} } }))"
                                        class="text-[13.5px] font-medium text-ink hover:text-accent transition-colors cursor-pointer text-left truncate block w-full">{{ $task->title }}</button>
                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                    @php
                                        $pColors = ['critical' => ['#fdf0f0','#b94040'], 'high' => ['#fdf0f0','#b94040'], 'medium' => ['#fef9ec','#9a7a1a'], 'low' => ['#edf7f2','#2e7d55']];
                                        $pc = $pColors[$task->priority] ?? ['#F5F4EF','#8c8c8a'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                                          style="background:{{ $pc[0] }};color:{{ $pc[1] }}">{{ ucfirst($task->priority) }}</span>
                                    @if($task->type)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-surface text-dim border border-hairline">{{ ucfirst($task->type) }}</span>
                                    @endif
                                    @if($task->sprint)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] text-muted">{{ $task->sprint->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <form action="{{ route('tasks.claim', $task) }}" method="POST" class="flex-shrink-0">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-[12px] font-semibold rounded-lg border cursor-pointer transition-colors duration-150"
                                        style="color:#2e7d55;background:#edf7f2;border-color:#b7e0ca"
                                        onmouseover="this.style.background='#d6f0e4'"
                                        onmouseout="this.style.background='#edf7f2'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59"/>
                                    </svg>
                                    Claim
                                </button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                {{-- Developer: My Tasks --}}
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="px-6 py-4 border-b border-hairline">
                        <h2 class="text-[14px] font-semibold text-ink">My Tasks</h2>
                        <p class="text-[12px] text-muted mt-0.5">Tasks assigned to you on this project</p>
                    </div>
                    @if($myTasks->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <p class="text-[14px] font-medium text-dim">No tasks assigned to you yet</p>
                        <p class="text-[13px] text-muted mt-1">Claim an open task above to get started.</p>
                    </div>
                    @else
                    <ul class="divide-y divide-hairline">
                        @foreach($myTasks as $task)
                        <li class="flex items-center gap-4 px-6 py-3 hover:bg-canvas transition-colors">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('tasks.show', $task) }}"
                                   class="text-[13.5px] font-medium text-ink hover:text-accent transition-colors truncate block">{{ $task->title }}</a>
                                @if($task->due_date)
                                <p class="text-[12px] {{ $task->isOverdue() ? 'text-[#b94040] font-medium' : 'text-muted' }} mt-0.5">
                                    Due {{ $task->due_date->format('M d, Y') }}
                                </p>
                                @endif
                            </div>
                            <div class="flex-shrink-0">
                                @include('components.badge', ['type' => 'status', 'value' => $task->status])
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @endif

            </div>

            {{-- Right col (2/5) --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Project Details --}}
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="px-5 py-4 border-b border-hairline">
                        <h2 class="text-[13px] font-semibold text-ink">Project Details</h2>
                    </div>
                    <dl class="divide-y divide-hairline">
                        <div class="px-5 py-3">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-muted mb-0.5">Start Date</dt>
                            <dd class="text-[13px] text-ink">{{ $project->start_date?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="px-5 py-3">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-muted mb-0.5">Deadline</dt>
                            <dd class="text-[13px] {{ $project->isOverdue() ? 'text-[#b94040] font-medium' : 'text-ink' }}">{{ $project->deadline?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="px-5 py-3">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-muted mb-0.5">Created</dt>
                            <dd class="text-[13px] text-ink">{{ $project->created_at->format('M d, Y') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Teams --}}
                @php $assignedTeamIds = $assignedTeams->pluck('id')->toArray(); @endphp
                <div x-data="{ open: false }"
                     class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-hairline">
                        <h2 class="text-[13px] font-semibold text-ink">Teams</h2>
                        @if($canManage)
                        <button @click="open = true"
                                class="inline-flex items-center gap-1 text-[11px] font-medium text-accent hover:underline cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Manage
                        </button>
                        @endif
                    </div>
                    @if($assignedTeams->isEmpty())
                    <div class="px-5 py-6 text-center">
                        <p class="text-[13px] text-muted">No teams assigned yet</p>
                        @if($canManage)
                        <button @click="open = true" class="inline-block mt-2 text-[12px] font-medium text-accent hover:underline cursor-pointer">Assign a team &rarr;</button>
                        @endif
                    </div>
                    @else
                    <ul class="divide-y divide-hairline">
                        @foreach($assignedTeams as $team)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="w-8 h-8 rounded-lg bg-accent-light flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-semibold text-ink truncate">{{ $team->name }}</p>
                                <p class="text-[11px] text-muted">{{ $team->members->count() }} {{ Str::plural('member', $team->members->count()) }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    @if($canManage)
                    <template x-teleport="body">
                        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                            <div class="absolute inset-0 bg-black/45" @click="open = false"></div>
                            <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] overflow-hidden"
                                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 @click.stop>
                                <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                                    <h3 class="text-[15px] font-semibold text-ink">Assign Teams</h3>
                                    <button @click="open = false" class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <form action="{{ route('projects.assign-teams', $project) }}" method="POST">
                                    @csrf
                                    @if($allTeams->isEmpty())
                                    <div class="px-6 py-8 text-center"><p class="text-[13px] text-muted">No teams exist yet.</p></div>
                                    @else
                                    <div class="px-3 py-3 space-y-1 max-h-72 overflow-y-auto">
                                        @foreach($allTeams as $team)
                                        @php $isAssigned = in_array($team->id, $assignedTeamIds); @endphp
                                        <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-canvas cursor-pointer transition-colors duration-100">
                                            <input type="checkbox" name="teams[]" value="{{ $team->id }}" @checked($isAssigned)
                                                   class="w-4 h-4 rounded border-line cursor-pointer accent-[#D97757]">
                                            <span class="flex-1 text-[13px] font-medium text-ink">{{ $team->name }}</span>
                                            @if($isAssigned)
                                            <span class="text-[11px] font-semibold text-[#2e7d55] bg-[#edf7f2] px-2 py-0.5 rounded-full">Assigned</span>
                                            @endif
                                        </label>
                                        @endforeach
                                    </div>
                                    @endif
                                    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-hairline">
                                        <button type="button" @click="open = false" class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">Cancel</button>
                                        <button type="submit" class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                    @endif
                </div>

                {{-- Guide preview --}}
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-hairline">
                        <h2 class="text-[13px] font-semibold text-ink">Project Guide</h2>
                        <button @click="setTab('guide')" class="text-[12px] font-medium text-accent hover:underline cursor-pointer">View full guide &rarr;</button>
                    </div>
                    @if($project->guide)
                    <div class="px-5 py-4">
                        <div class="prose prose-sm prose-neutral max-w-none text-dim line-clamp-6 text-[13px] leading-relaxed [&>*:first-child]:mt-0 [&>*:last-child]:mb-0">{!! Str::markdown(mb_substr($project->guide, 0, 500)) !!}</div>
                        <button @click="setTab('guide')" class="mt-3 text-[12px] font-medium text-accent hover:underline cursor-pointer">Read more &rarr;</button>
                    </div>
                    @else
                    <div class="px-5 py-6 text-center">
                        <p class="text-[13px] text-muted">No guide yet</p>
                        @if($canManage)
                        <button @click="setTab('guide')" class="inline-block mt-2 text-[12px] font-medium text-accent hover:underline cursor-pointer">Write a guide &rarr;</button>
                        @endif
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Sprints & Tasks tab (managers only)                                   --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($canManage)
    <div x-show="tab === 'sprints'">
        <livewire:project-sprints :project="$project" />
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Backlog tab (managers only)                                           --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'backlog'">
        <livewire:project-backlog :project="$project" />
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Guide tab                                                             --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'guide'"
         x-data="{ editing: false, guide: {{ Js::from($project->guide ?? '') }} }">
        <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
            <div class="flex items-center justify-between px-6 py-5 border-b border-hairline">
                <h2 class="text-[16px] font-semibold text-ink">Project Guide</h2>
                @if($canManage)
                <div class="flex items-center gap-2">
                    <button x-show="!editing" @click="editing = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors duration-150 cursor-pointer">
                        @include('components.icon', ['name' => 'pencil'])
                        Edit
                    </button>
                    <button x-show="editing" x-cloak @click="editing = false"
                            class="px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                        Cancel
                    </button>
                </div>
                @endif
            </div>

            {{-- Edit form (managers only, when editing) --}}
            @if($canManage)
            <div x-show="editing" x-cloak class="px-6 py-5">
                <form action="{{ route('projects.update', $project) }}" method="POST">
                    @csrf @method('PATCH')
                    <textarea name="guide" x-model="guide" rows="20"
                              placeholder="Write your project guide in Markdown — headings, bullet points, code blocks..."
                              class="w-full px-4 py-3 text-[13px] font-mono text-ink bg-surface border border-line rounded-xl placeholder:text-muted placeholder:italic focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-y leading-relaxed"></textarea>
                    <div class="flex justify-end mt-3">
                        <button type="submit"
                                class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                            Save guide
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- Rendered guide (when not editing) --}}
            <div x-show="!editing">
                @if($project->guide)
                <div class="px-6 py-6 prose prose-neutral max-w-none
                            prose-headings:text-ink prose-headings:font-semibold
                            prose-p:text-dim prose-p:text-[13px] prose-p:leading-relaxed
                            prose-li:text-dim prose-li:text-[13px]
                            prose-strong:text-ink prose-strong:font-semibold
                            prose-code:text-[12px] prose-code:bg-surface prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-ink prose-code:font-mono
                            prose-pre:bg-surface prose-pre:border prose-pre:border-hairline prose-pre:rounded-xl
                            prose-a:text-accent prose-a:no-underline hover:prose-a:underline
                            prose-hr:border-hairline">
                    {!! Str::markdown($project->guide, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                </div>
                @else
                <div class="px-6 py-16 text-center">
                    <svg class="w-12 h-12 text-muted mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    <p class="text-[15px] font-medium text-dim">No guide written yet</p>
                    <p class="text-[13px] text-muted mt-1">Document conventions, stack details, and onboarding notes.</p>
                    @if($canManage)
                    <button @click="editing = true"
                            class="inline-flex items-center gap-1.5 mt-5 px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                        Write a guide
                    </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

<livewire:task-modal />
@if(! $canManage)
<script>
    window.addEventListener('task-claimed', () => window.location.reload());
</script>
@endif

</x-layouts.app>
