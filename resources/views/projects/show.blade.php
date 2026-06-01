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
     x-init="$dispatch('ai-project-hint', { projectId: {{ $project->id }} })"
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
                            @if($project->website)
                            <a href="{{ $project->website }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 text-[12px] text-dim hover:text-accent transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                {{ parse_url($project->website, PHP_URL_HOST) ?: $project->website }}
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
            Documents
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

                {{-- Documents preview --}}
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-hairline">
                        <h2 class="text-[13px] font-semibold text-ink">Project Documents</h2>
                        <button @click="setTab('guide')" class="text-[12px] font-medium text-accent hover:underline cursor-pointer">Manage documents &rarr;</button>
                    </div>
                    @if($project->projectDocuments->isNotEmpty())
                    <div class="px-5 py-4">
                        <p class="text-[13px] text-dim mb-3">{{ $project->projectDocuments->count() }} {{ Str::plural('document', $project->projectDocuments->count()) }} available</p>
                        <div class="space-y-2">
                            @foreach($project->projectDocuments->take(3) as $doc)
                            <div class="border border-hairline rounded-lg px-3 py-2 bg-surface">
                                <p class="text-[12px] font-semibold text-ink truncate">{{ $doc->title }}</p>
                                <p class="text-[11px] text-muted mt-0.5 line-clamp-2">{{ \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $doc->content)), 150) }}</p>
                            </div>
                            @endforeach
                        </div>
                        <button @click="setTab('guide')" class="mt-3 text-[12px] font-medium text-accent hover:underline cursor-pointer">Open documents &rarr;</button>
                    </div>
                    @else
                    <div class="px-5 py-6 text-center">
                        <p class="text-[13px] text-muted">No documents yet</p>
                        @if($canManage)
                        <button @click="setTab('guide')" class="inline-block mt-2 text-[12px] font-medium text-accent hover:underline cursor-pointer">Add a document &rarr;</button>
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
    <div
        x-show="tab === 'guide'"
        x-data="{
            docs: {{ Js::from($project->projectDocuments->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'type' => $doc->type,
                'content' => $doc->content,
                'rendered_html' => \Illuminate\Support\Str::markdown($doc->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                'sort_order' => $doc->sort_order,
            ])->values()) }},
            canManage: {{ $canManage ? 'true' : 'false' }},
            adding: false,
            editingId: null,
            viewingDoc: null,
            saving: false,
            error: '',
            form: { title: '', type: '', content: '' },
            storeUrl: '{{ route('projects.documents.store', $project) }}',
            updateRouteTemplate: '{{ route('projects.documents.update', ['project' => $project, 'doc' => '__DOC__']) }}',
            deleteRouteTemplate: '{{ route('projects.documents.destroy', ['project' => $project, 'doc' => '__DOC__']) }}',
            typeSuggestions: ['Guide', 'Style Guide', 'Architecture', 'API Docs', 'Notes'],
            get sortedDocs() {
                return [...this.docs].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0) || a.id - b.id);
            },
            csrf() {
                const token = document.querySelector('meta[name=csrf-token]');
                return token ? token.getAttribute('content') : '';
            },
            resetForm() {
                this.form = { title: '', type: '', content: '' };
                this.editingId = null;
                this.adding = false;
                this.error = '';
            },
            openAdd() {
                this.resetForm();
                this.adding = true;
            },
            openEdit(doc) {
                this.error = '';
                this.adding = false;
                this.editingId = doc.id;
                this.form = {
                    title: doc.title ?? '',
                    type: doc.type ?? '',
                    content: doc.content ?? '',
                };
            },
            cancelEdit() {
                this.resetForm();
            },
            onFilePicked(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => { this.form.content = String(e.target.result || ''); };
                reader.readAsText(file);
            },
            updateUrl(id) {
                return this.updateRouteTemplate.replace('__DOC__', String(id));
            },
            deleteUrl(id) {
                return this.deleteRouteTemplate.replace('__DOC__', String(id));
            },
            async saveDoc() {
                if (!this.canManage || this.saving) return;
                if (String(this.form.title).trim() === '' || String(this.form.content).trim() === '') {
                    this.error = 'Title and content are required.';
                    return;
                }

                this.saving = true;
                this.error = '';

                const isEdit = this.editingId !== null;
                const url = isEdit ? this.updateUrl(this.editingId) : this.storeUrl;
                const method = isEdit ? 'PATCH' : 'POST';

                try {
                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({
                            title: this.form.title,
                            type: this.form.type,
                            content: this.form.content,
                        }),
                    });

                    const json = await res.json();
                    if (!res.ok) {
                        this.error = json.message || 'Could not save document.';
                        this.saving = false;
                        return;
                    }

                    const saved = json.data;
                    if (isEdit) {
                        const idx = this.docs.findIndex(d => d.id === saved.id);
                        if (idx >= 0) this.docs.splice(idx, 1, saved);
                    } else {
                        this.docs.push(saved);
                    }

                    this.resetForm();
                } catch (e) {
                    this.error = 'Network error while saving.';
                }

                this.saving = false;
            },
            async deleteDoc(doc) {
                if (!this.canManage) return;
                if (!confirm(`Delete ${doc.title}?`)) return;

                try {
                    const res = await fetch(this.deleteUrl(doc.id), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                    });

                    if (!res.ok) {
                        this.error = 'Could not delete document.';
                        return;
                    }

                    this.docs = this.docs.filter(d => d.id !== doc.id);
                    if (this.viewingDoc && this.viewingDoc.id === doc.id) {
                        this.viewingDoc = null;
                    }
                    if (this.editingId === doc.id) {
                        this.resetForm();
                    }
                } catch (e) {
                    this.error = 'Network error while deleting.';
                }
            },
            preview(content) {
                const normalized = String(content || '').trim().replace(/\s+/g, ' ');
                return normalized.length > 150 ? normalized.slice(0, 150) + '...' : normalized;
            }
        }"
    >
        <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
            <div class="flex items-center justify-between px-6 py-5 border-b border-hairline">
                <h2 class="text-[16px] font-semibold text-ink">Project Documents</h2>
                @if($canManage)
                <button
                    x-on:click="openAdd()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer"
                >
                    @include('components.icon', ['name' => 'plus'])
                    Add document
                </button>
                @endif
            </div>

            <div class="px-6 py-5 space-y-4">
                <div x-show="error" x-text="error" class="text-[12px] text-[#b94040] bg-[#fff5f5] border border-[#f5c6c6] rounded-lg px-3 py-2"></div>

                @if($canManage)
                <div x-show="adding || editingId !== null" x-cloak class="border border-line rounded-xl p-4 bg-surface space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Title</label>
                            <input x-model="form.title" type="text" class="w-full px-3 py-2 text-[13px] bg-white border border-line rounded-lg outline-none focus:border-accent" placeholder="Document title">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Type</label>
                            <input x-model="form.type" list="doc-type-suggestions" type="text" class="w-full px-3 py-2 text-[13px] bg-white border border-line rounded-lg outline-none focus:border-accent" placeholder="Guide, Architecture, Notes...">
                            <datalist id="doc-type-suggestions">
                                <template x-for="s in typeSuggestions" :key="s"><option :value="s"></option></template>
                            </datalist>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Content</label>
                        <textarea x-model="form.content" rows="12" class="w-full px-3 py-2 text-[13px] font-mono bg-white border border-line rounded-lg outline-none focus:border-accent resize-y" placeholder="Write markdown content..."></textarea>
                    </div>

                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <label class="inline-flex items-center gap-2 px-3 py-1.5 text-[12px] font-medium text-dim bg-white border border-line rounded-lg cursor-pointer hover:bg-hairline transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            Import .md file
                            <input type="file" accept=".md" class="sr-only" x-on:change="onFilePicked($event)">
                        </label>

                        <div class="flex items-center gap-2">
                            <button x-on:click="cancelEdit()" type="button" class="px-3 py-1.5 text-[12px] font-medium text-ink bg-white border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">Cancel</button>
                            <button x-on:click="saveDoc()" type="button" x-bind:disabled="saving" class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors cursor-pointer disabled:opacity-60" x-text="saving ? 'Saving...' : (editingId !== null ? 'Update document' : 'Create document')"></button>
                        </div>
                    </div>
                </div>
                @endif

                <template x-if="sortedDocs.length === 0">
                    <div class="py-16 text-center">
                        <svg class="w-12 h-12 text-muted mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        <p class="text-[15px] font-medium text-dim">No documents yet</p>
                    </div>
                </template>

                <div class="space-y-3" x-show="sortedDocs.length > 0">
                    <template x-for="doc in sortedDocs" :key="doc.id">
                        <div class="border border-line rounded-xl p-4 bg-white">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[14px] font-semibold text-ink truncate" x-text="doc.title"></p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span x-show="doc.type" x-text="doc.type" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-surface text-dim border border-hairline"></span>
                                        <span class="text-[11px] text-muted" x-text="(doc.content || '').length + ' chars'"></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button x-on:click="viewingDoc = doc" class="px-2.5 py-1 rounded-md text-[11px] font-medium border border-line text-dim hover:text-ink hover:bg-surface transition-colors cursor-pointer">View</button>
                                    @if($canManage)
                                    <button x-on:click="openEdit(doc)" class="px-2.5 py-1 rounded-md text-[11px] font-medium border border-line text-dim hover:text-ink hover:bg-surface transition-colors cursor-pointer">Edit</button>
                                    <button x-on:click="deleteDoc(doc)" class="px-2.5 py-1 rounded-md text-[11px] font-medium border border-[#f5c6c6] text-[#b94040] hover:bg-[#fff5f5] transition-colors cursor-pointer">Delete</button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-[13px] text-dim mt-2 leading-relaxed" x-text="preview(doc.content)"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div
            x-show="viewingDoc"
            x-cloak
            x-transition:enter="transition-opacity duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[70] bg-black/40 p-4"
        >
            <div class="mx-auto max-w-4xl h-full bg-white rounded-xl border border-line shadow-[0_20px_60px_rgba(0,0,0,0.18)] flex flex-col">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-hairline">
                    <div class="min-w-0">
                        <p class="text-[14px] font-semibold text-ink truncate" x-text="viewingDoc?.title ?? ''"></p>
                        <p class="text-[11px] text-muted" x-text="viewingDoc?.type ?? ''"></p>
                    </div>
                    <button x-on:click="viewingDoc = null" class="ml-auto w-7 h-7 rounded-full inline-flex items-center justify-center bg-surface text-muted hover:text-ink transition-colors cursor-pointer" title="Close">×</button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div class="prose prose-sm max-w-none" x-html="viewingDoc?.rendered_html ?? ''"></div>
                </div>
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
