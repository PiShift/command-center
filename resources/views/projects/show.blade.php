<x-layouts.app :title="$project->name">

<div class="max-w-6xl mx-auto space-y-6">

    @include('components.flash')

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <div class="flex">
            {{-- Color stripe --}}
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
                            @if($project->customer)
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

    {{-- ── Stat cards ───────────────────────────────────────────────────────── --}}
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

    {{-- ── Progress bar ─────────────────────────────────────────────────────── --}}
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

    {{-- ── Two-column body ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        {{-- Left column (60%) --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Sprints --}}
            <div
                x-data="{
                    showModal: false,
                    editing: null,
                    form: { name: '', description: '', deadline: '' },
                    open(sprint) {
                        if (sprint) {
                            this.editing = sprint;
                            this.form.name = sprint.name;
                            this.form.description = sprint.description ?? '';
                            this.form.deadline = sprint.deadline ?? '';
                        } else {
                            this.editing = null;
                            this.form = { name: '', description: '', deadline: '' };
                        }
                        this.showModal = true;
                    },
                    close() { this.showModal = false; }
                }"
                class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]"
            >
                {{-- Section header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                    <h2 class="text-[14px] font-semibold text-ink">Sprints</h2>
                    @if($canManage)
                    <button @click="open(null)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors duration-150 cursor-pointer">
                        @include('components.icon', ['name' => 'plus'])
                        Add sprint
                    </button>
                    @endif
                </div>

                {{-- Sprint list or empty state --}}
                @if($sprints->isEmpty())
                    <div class="px-6 py-12 flex flex-col items-center text-center">
                        <svg class="w-10 h-10 text-muted mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/>
                        </svg>
                        <p class="text-[14px] font-medium text-dim">No sprints yet</p>
                        <p class="text-[13px] text-muted mt-1">Break the project into key checkpoints.</p>
                        @if($canManage)
                        <button @click="open(null)"
                                class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors duration-150 cursor-pointer">
                            @include('components.icon', ['name' => 'plus'])
                            Add first sprint
                        </button>
                        @endif
                    </div>
                @else
                    <ul class="divide-y divide-hairline max-h-[420px] overflow-y-auto">
                        @foreach($sprints as $sprint)
                        <li class="px-6 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    {{-- Name + status + deadline row --}}
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <span class="text-[14px] font-semibold text-ink">{{ $sprint->name }}</span>
                                        {{-- Status badge --}}
                                        @if($sprint->status === 'draft')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-surface text-muted">Draft</span>
                                        @elseif($sprint->status === 'active')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#edf7f2] text-[#2e7d55]">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#eef3fb] text-[#3a6fba]">Completed</span>
                                        @endif
                                        @if($sprint->deadline)
                                            @php $isPast = $sprint->deadline->isPast(); @endphp
                                            <span class="text-[12px] font-medium {{ $isPast ? 'text-[#b94040]' : 'text-muted' }}">
                                                {{ $sprint->deadline->format('M d, Y') }}
                                                @if($isPast) · Overdue @endif
                                            </span>
                                        @endif
                                    </div>
                                    {{-- Description --}}
                                    @if($sprint->description)
                                        <p class="text-[13px] text-dim mt-1">{{ $sprint->description }}</p>
                                    @endif
                                    {{-- Task count breakdown --}}
                                    @php
                                        $sprintTasks      = $sprint->tasks;
                                        $sprintTotal      = $sprintTasks->count();
                                        $sprintOpen       = $sprintTasks->where('status', 'open')->count();
                                        $sprintInProgress = $sprintTasks->whereIn('status', ['todo', 'in-progress'])->count();
                                        $sprintDone       = $sprintTasks->where('status', 'done')->count();
                                    @endphp
                                    @if($sprintTotal > 0)
                                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                                        <span class="text-[12px] text-muted font-medium">{{ $sprintTotal }} task{{ $sprintTotal !== 1 ? 's' : '' }}</span>
                                        @if($sprintOpen > 0)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-surface text-muted">
                                            <span class="w-1.5 h-1.5 rounded-full bg-muted inline-block"></span>
                                            {{ $sprintOpen }} open
                                        </span>
                                        @endif
                                        @if($sprintInProgress > 0)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-accent-light text-accent">
                                            <span class="w-1.5 h-1.5 rounded-full bg-accent inline-block"></span>
                                            {{ $sprintInProgress }} in progress
                                        </span>
                                        @endif
                                        @if($sprintDone > 0)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-[#edf7f2] text-[#2e7d55]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#2e7d55] inline-block"></span>
                                            {{ $sprintDone }} done
                                        </span>
                                        @endif
                                    </div>
                                    @endif
                                    {{-- Progress bar --}}
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Progress</span>
                                            <span class="text-[11px] font-semibold text-muted">
                                                {{ $sprint->progress_percent }}% complete
                                                @if($sprint->total_points > 0)
                                                    · {{ $sprint->done_points }} / {{ $sprint->total_points }} pts
                                                @endif
                                            </span>
                                        </div>
                                        <div class="w-full h-1.5 bg-surface rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-accent transition-all duration-300"
                                                 style="width: {{ $sprint->progress_percent }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                @if($canManage)
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    @if($sprint->status !== 'completed')
                                    <button
                                        @click="open({
                                            id: {{ $sprint->id }},
                                            name: {{ Js::from($sprint->name) }},
                                            description: {{ Js::from($sprint->description) }},
                                            deadline: '{{ $sprint->deadline?->format('Y-m-d') ?? '' }}'
                                        })"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer"
                                        title="Edit sprint">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </button>
                                    @endif

                                    {{-- Draft: Publish button (only if has tasks) --}}
                                    @if($sprint->status === 'draft' && $sprint->tasks->count() > 0)
                                        @if($sprintOpen > 0)
                                        <form action="{{ route('sprints.publish', [$project, $sprint]) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-[#2e7d55] bg-[#edf7f2] hover:bg-[#d6f0e3] rounded-lg transition-colors duration-150 cursor-pointer"
                                                    title="Publish sprint">
                                                Publish
                                            </button>
                                        </form>
                                        @else
                                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold text-muted bg-surface rounded-lg cursor-not-allowed"
                                              title="No open tasks to publish. Promote backlog items to this sprint first.">
                                            No open tasks
                                        </span>
                                        @endif
                                    @endif

                                    {{-- Active: Unpublish + Mark Complete --}}
                                    @if($sprint->status === 'active')
                                    <form action="{{ route('sprints.unpublish', [$project, $sprint]) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-muted bg-surface hover:bg-hairline rounded-lg transition-colors duration-150 cursor-pointer"
                                                title="Move back to draft">
                                            Unpublish
                                        </button>
                                    </form>
                                    <form action="{{ route('sprints.complete', [$project, $sprint]) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-[#3a6fba] bg-[#eef3fb] hover:bg-[#dce8f9] rounded-lg transition-colors duration-150 cursor-pointer"
                                                title="Mark sprint as completed">
                                            Mark Complete
                                        </button>
                                    </form>
                                    @endif

                                    @if($sprint->status !== 'completed')
                                    <button
                                        x-data
                                        @click="if(confirm('Delete this sprint? This cannot be undone.')) $refs.deleteForm{{ $sprint->id }}.submit()"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-[#b94040] hover:bg-[#fff0f0] transition-colors duration-150 cursor-pointer"
                                        title="Delete sprint">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                    <form x-ref="deleteForm{{ $sprint->id }}"
                                          action="{{ route('sprints.destroy', [$project, $sprint]) }}"
                                          method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif

                {{-- ── Create / Edit modal ────────────────────────────────────────────── --}}
                <template x-teleport="body">
                    <div x-show="showModal" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">

                        {{-- Overlay --}}
                        <div class="absolute inset-0 bg-black/45" @click="close()"></div>

                        {{-- Modal box --}}
                        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] overflow-hidden"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.stop>

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                                <h3 class="text-[15px] font-semibold text-ink"
                                    x-text="editing ? 'Edit Sprint' : 'New Sprint'"></h3>
                                <button @click="close()"
                                        class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Create form --}}
                            <form x-show="!editing"
                                  action="{{ route('sprints.store', $project) }}"
                                  method="POST">
                                @csrf
                                <div class="px-6 py-5 space-y-4">
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Name <span class="text-[#b94040]">*</span></label>
                                        <input type="text" name="name" x-model="form.name" required maxlength="255"
                                               placeholder="e.g. Sprint 1 — MVP"
                                               class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg placeholder:text-muted placeholder:italic focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                                        <textarea name="description" x-model="form.description" rows="4"
                                                  placeholder="Optional context..."
                                                  class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg placeholder:text-muted placeholder:italic focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Deadline</label>
                                        <input type="date" name="deadline" x-model="form.deadline"
                                               class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-hairline bg-canvas">
                                    <button type="button" @click="close()"
                                            class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                                        Save Sprint
                                    </button>
                                </div>
                            </form>

                            {{-- Edit form (one per sprint, rendered server-side and selected via Alpine) --}}
                            <template x-if="editing">
                                <div>
                                    @foreach($sprints as $sprint)
                                    <form x-show="editing && editing.id === {{ $sprint->id }}"
                                          action="{{ route('sprints.update', [$project, $sprint]) }}"
                                          method="POST">
                                        @csrf @method('PATCH')
                                        <div class="px-6 py-5 space-y-4">
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Name <span class="text-[#b94040]">*</span></label>
                                                <input type="text" name="name" x-model="form.name" required maxlength="255"
                                                       class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg placeholder:text-muted focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                                                <textarea name="description" x-model="form.description" rows="4"
                                                          class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg placeholder:text-muted focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Deadline</label>
                                                <input type="date" name="deadline" x-model="form.deadline"
                                                       class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-hairline bg-canvas">
                                            <button type="button" @click="close()"
                                                    class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                    class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                                                Update Sprint
                                            </button>
                                        </div>
                                    </form>
                                    @endforeach
                                </div>
                            </template>

                        </div>
                    </div>
                </template>

            </div>
            {{-- ── Backlog (managers only) ──────────────────────────────────────────── --}}
            @if($canManage)
            @php
                $promotedItems = $project->backlogItems->where('promoted', true)->values();
            @endphp
            <div
                x-data="{
                    showModal: false,
                    editing: null,
                    showPromoted: false,
                    showPromoteModal: false,
                    promoting: null,
                    promoteForm: { title: '', description: '', type: 'feature', priority: 'medium', sprint_id: '', weight: 3, assigned_to: '', due_date: '' },
                    form: { title: '', description: '', guide: '', sprint_id: '', guideFilename: '' },
                    selectedItems: [],
                    allItemIds: {{ Js::from($backlogItems->pluck('id')) }},
                    itemSprints: {{ Js::from($backlogItems->pluck('sprint_id', 'id')) }},
                    bulkSprint: '',
                    get allSelected() { return this.allItemIds.length > 0 && this.selectedItems.length === this.allItemIds.length; },
                    get someSelected() { return this.selectedItems.length > 0 && this.selectedItems.length < this.allItemIds.length; },
                    toggleAll() {
                        if (this.allSelected) {
                            this.selectedItems = [];
                        } else {
                            this.selectedItems = [...this.allItemIds];
                        }
                        this.updateBulkSprint();
                    },
                    toggleItem(id) {
                        const idx = this.selectedItems.indexOf(id);
                        if (idx === -1) { this.selectedItems.push(id); } else { this.selectedItems.splice(idx, 1); }
                        this.updateBulkSprint();
                    },
                    clearSelection() { this.selectedItems = []; this.bulkSprint = ''; },
                    updateBulkSprint() {
                        if (this.selectedItems.length === 0) { this.bulkSprint = ''; return; }
                        const sprintIds = this.selectedItems.map(id => this.itemSprints[id] ?? '');
                        const first = sprintIds[0];
                        this.bulkSprint = sprintIds.every(s => s === first) ? (first || '') : '';
                    },
                    open(item) {
                        if (item) {
                            this.editing = item;
                            this.form.title = item.title;
                            this.form.description = item.description ?? '';
                            this.form.guide = item.guide ?? '';
                            this.form.sprint_id = item.sprint_id ?? '';
                            this.form.guideFilename = '';
                        } else {
                            this.editing = null;
                            this.form = { title: '', description: '', guide: '', sprint_id: '', guideFilename: '' };
                        }
                        this.showModal = true;
                    },
                    close() { this.showModal = false; },
                    openPromote(item) {
                        this.promoting = item;
                        this.promoteForm.title = item.title;
                        this.promoteForm.description = item.description ?? '';
                        this.promoteForm.type = 'feature';
                        this.promoteForm.priority = 'medium';
                        this.promoteForm.sprint_id = item.sprint_id ?? '';
                        this.promoteForm.weight = 3;
                        this.promoteForm.assigned_to = '';
                        this.promoteForm.due_date = '';
                        this.showPromoteModal = true;
                    },
                    closePromote() { this.showPromoteModal = false; this.promoting = null; },
                    loadGuide(event) {
                        const file = event.target.files[0];
                        if (!file) return;
                        this.form.guideFilename = file.name;
                        const reader = new FileReader();
                        reader.onload = (e) => { this.form.guide = e.target.result; };
                        reader.readAsText(file);
                    },
                    // ── AI Planning ──────────────────────────────────────────
                    aiPlanOpen: false,
                    planStep: 1,
                    planTab: 'paste',
                    planNotes: '',
                    planSelectedItems: [],
                    planLoading: false,
                    planError: '',
                    planResult: null,
                    planRawInput: '',
                    togglePlanItem(id) {
                        const idx = this.planSelectedItems.indexOf(id);
                        if (idx === -1) { this.planSelectedItems.push(id); } else { this.planSelectedItems.splice(idx, 1); }
                    },
                    async analyzePlan() {
                        this.planError = '';
                        if (this.planTab === 'paste' && !this.planNotes.trim()) {
                            this.planError = 'Please paste some planning notes.'; return;
                        }
                        if (this.planTab === 'backlog' && this.planSelectedItems.length === 0) {
                            this.planError = 'Select at least one backlog item.'; return;
                        }
                        const body = this.planTab === 'paste'
                            ? { raw_notes: this.planNotes }
                            : { item_ids: this.planSelectedItems };
                        const rawInput = this.planTab === 'paste'
                            ? this.planNotes
                            : this.planSelectedItems.join(', ');
                        this.planRawInput = rawInput;
                        this.planLoading = true;
                        try {
                            const res = await fetch('{{ route('ai.plan', $project) }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                body: JSON.stringify(body)
                            });
                            const json = await res.json();
                            if (!res.ok) { this.planError = json.error || 'AI request failed.'; }
                            else { this.planResult = json; this.planStep = 2; }
                        } catch(e) { this.planError = 'Network error. Please try again.'; }
                        this.planLoading = false;
                    },
                    removeItem(sIdx, iIdx) {
                        this.planResult.sprints[sIdx].items.splice(iIdx, 1);
                    },
                    removeSprint(sIdx) {
                        this.planResult.sprints.splice(sIdx, 1);
                    },
                    addSprint() {
                        this.planResult.sprints.push({ name: 'New Sprint', rationale: '', items: [] });
                    },
                    moveItem(fromSprint, fromItem, toSprintIdx) {
                        const item = this.planResult.sprints[fromSprint].items.splice(fromItem, 1)[0];
                        this.planResult.sprints[toSprintIdx].items.push(item);
                    },
                    // ── Promote AI suggestions ────────────────────────────────
                    promoteAiLoading: false,
                    promoteAiError: '',
                    promoteAiResult: null,
                    async fetchPromoteSuggestions() {
                        this.promoteAiLoading = true;
                        this.promoteAiError = '';
                        this.promoteAiResult = null;
                        try {
                            const res = await fetch('{{ route('ai.promote-suggestions', $project) }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                body: JSON.stringify({ title: this.promoting?.title ?? '', description: this.promoting?.description ?? '' })
                            });
                            const json = await res.json();
                            if (!res.ok) { this.promoteAiError = json.error || 'AI request failed.'; }
                            else { this.promoteAiResult = json; }
                        } catch(e) { this.promoteAiError = 'Network error. Please try again.'; }
                        this.promoteAiLoading = false;
                    },
                    applyAiSuggestion(field, value) {
                        if (field === 'type')        this.promoteForm.type = value;
                        if (field === 'priority')    this.promoteForm.priority = value;
                        if (field === 'weight')      this.promoteForm.weight = parseInt(value);
                        if (field === 'description') this.promoteForm.description = value;
                    }
                }"
                class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]"
            >
                {{-- Section header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                    <div class="flex items-center gap-3">
                        @if($backlogItems->count() > 0 && $canManage)
                        <input type="checkbox"
                               @click="toggleAll()"
                               :checked="allSelected"
                               :indeterminate.prop="someSelected"
                               class="w-4 h-4 rounded border-line cursor-pointer accent-[#D97757]">
                        @endif
                        <div class="flex items-center gap-2">
                            <h2 class="text-[14px] font-semibold text-ink">Backlog</h2>
                            @if($backlogItems->count() > 0)
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold text-muted bg-surface border border-hairline rounded-full">
                                {{ $backlogItems->count() }}
                            </span>
                            @endif
                        </div>
                    </div>

                    @if($canManage)
                    {{-- Right side: bulk actions (when selected) OR add button --}}
                    <div class="flex items-center gap-2">

                        {{-- Bulk actions — visible only when items are selected --}}
                        @if($backlogItems->count() > 0)
                        <div x-show="selectedItems.length > 0" x-cloak class="flex items-center gap-2">

                            {{-- Count badge --}}
                            <span class="text-[12px] font-medium text-muted" x-text="selectedItems.length + ' selected'"></span>
                            <span class="w-px h-4 bg-hairline"></span>

                            {{-- Sprint assign --}}
                            @if($sprints->count() > 0)
                            <form x-ref="bulkSprintForm"
                                  action="{{ route('backlog.bulk-sprint', $project) }}"
                                  method="POST" class="flex items-center gap-1.5">
                                @csrf @method('PATCH')
                                <template x-for="id in selectedItems" :key="id">
                                    <input type="hidden" name="items[]" :value="id">
                                </template>
                                <select name="sprint_id"
                                        x-model="bulkSprint"
                                        class="text-[12px] text-ink bg-surface border border-line rounded-lg px-2 py-1 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 cursor-pointer">
                                    <option value="">Sprint…</option>
                                    @foreach($sprints as $sprint)
                                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" title="Assign sprint"
                                        class="h-7 px-2 text-[11px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors duration-150 cursor-pointer">
                                    Apply
                                </button>
                            </form>
                            @endif

                            {{-- Promote --}}
                            <form x-ref="bulkPromoteForm"
                                  action="{{ route('backlog.bulk-promote', $project) }}"
                                  method="POST">
                                @csrf
                                <template x-for="id in selectedItems" :key="id">
                                    <input type="hidden" name="items[]" :value="id">
                                </template>
                                <button type="button" title="Promote to tasks"
                                        @click="if(confirm('Promote ' + selectedItems.length + ' item' + (selectedItems.length === 1 ? '' : 's') + ' to tasks?')) $refs.bulkPromoteForm.submit()"
                                        class="inline-flex items-center gap-1 h-7 px-2.5 text-[11px] font-medium text-[#2e7d55] bg-[#edf7f2] border border-[#b7e0ca] rounded-lg hover:bg-[#d6f0e4] transition-colors duration-150 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
                                    </svg>
                                    Promote
                                </button>
                            </form>

                            {{-- Delete --}}
                            <form x-ref="bulkDeleteForm"
                                  action="{{ route('backlog.bulk-delete', $project) }}"
                                  method="POST">
                                @csrf @method('DELETE')
                                <template x-for="id in selectedItems" :key="id">
                                    <input type="hidden" name="items[]" :value="id">
                                </template>
                                <button type="button" title="Delete selected"
                                        @click="if(confirm('Delete ' + selectedItems.length + ' item' + (selectedItems.length === 1 ? '' : 's') + '? This cannot be undone.')) $refs.bulkDeleteForm.submit()"
                                        class="inline-flex items-center justify-center h-7 w-7 text-[#b94040] bg-[#fff0f0] border border-[#ffd0d0] rounded-lg hover:bg-[#ffe0e0] transition-colors duration-150 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </button>
                            </form>

                            {{-- Clear selection --}}
                            <button @click="clearSelection()" title="Clear selection"
                                    class="inline-flex items-center justify-center h-7 w-7 text-muted bg-surface border border-hairline rounded-lg hover:bg-hairline hover:text-dim transition-colors duration-150 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        @endif

                        {{-- Add item button — hidden when items selected --}}
                        <div x-show="selectedItems.length === 0" class="flex items-center gap-2">
                            {{-- Plan with AI --}}
                            <button @click="aiPlanOpen = true; planStep = 1; planNotes = ''; planSelectedItems = []; planResult = null; planError = ''"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-accent" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM7.5 12a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM18.894 6.166a.75.75 0 0 0-1.06-1.06l-1.591 1.59a.75.75 0 1 0 1.06 1.061l1.591-1.59ZM21.75 12a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5H21a.75.75 0 0 1 .75.75ZM17.834 18.894a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 1 0-1.061 1.06l1.59 1.591ZM12 18a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-2.25A.75.75 0 0 1 12 18ZM7.758 17.303a.75.75 0 0 0-1.061-1.06l-1.591 1.59a.75.75 0 0 0 1.06 1.061l1.592-1.59ZM6 12a.75.75 0 0 1-.75.75H3a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 6 12ZM6.697 7.757a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 0 0-1.061 1.06l1.59 1.591Z"/>
                                </svg>
                                Plan with AI
                            </button>
                            <button @click="open(null)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors duration-150 cursor-pointer">
                                @include('components.icon', ['name' => 'plus'])
                                Add item
                            </button>
                        </div>

                    </div>
                    @endif
                </div>

                {{-- Pending items or empty state --}}
                @if($backlogItems->isEmpty())
                    <div class="px-6 py-12 flex flex-col items-center text-center">
                        <svg class="w-10 h-10 text-muted mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                        <p class="text-[14px] font-medium text-dim">No backlog items yet</p>
                        <p class="text-[13px] text-muted mt-1">Add your first feature idea.</p>
                        @if($canManage)
                        <button @click="open(null)"
                                class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors duration-150 cursor-pointer">
                            @include('components.icon', ['name' => 'plus'])
                            Add first item
                        </button>
                        @endif
                    </div>
                @else
                    <div class="max-h-[480px] overflow-y-auto">
                    @php
                        $groupedItems = $backlogItems->groupBy('sprint_id');
                        // Put null sprint_id group last
                        $sprintGroups = collect();
                        foreach ($groupedItems as $sprintId => $groupItems) {
                            if ($sprintId) $sprintGroups[$sprintId] = $groupItems;
                        }
                        $noSprintItems = $groupedItems->get('') ?? $groupedItems->get(null) ?? collect();
                    @endphp
                    @foreach($sprintGroups as $sprintId => $groupItems)
                        @php $sprintLabel = $groupItems->first()->sprint?->name ?? 'Sprint'; @endphp
                        <div class="border-b border-hairline last:border-b-0">
                            <div class="px-6 py-2 bg-canvas">
                                <span class="text-[10px] font-semibold tracking-widest uppercase text-muted">{{ $sprintLabel }}</span>
                            </div>
                            <ul class="divide-y divide-hairline">
                                @foreach($groupItems as $item)
                                @include('projects._backlog_item_row', compact('item', 'canManage'))
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                    @if($noSprintItems->isNotEmpty())
                        <div class="border-b border-hairline last:border-b-0">
                            <div class="px-6 py-2 bg-canvas">
                                <span class="text-[10px] font-semibold tracking-widest uppercase text-muted">No sprint assigned</span>
                            </div>
                            <ul class="divide-y divide-hairline">
                                @foreach($noSprintItems as $item)
                                @include('projects._backlog_item_row', compact('item', 'canManage'))
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    </div>
                @endif

                {{-- Promoted items (collapsed by default) --}}
                @if($promotedItems->count() > 0)
                <div x-data="{ open: false }" class="border-t border-hairline">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-6 py-3 text-[12px] font-medium text-muted hover:text-dim hover:bg-canvas transition-colors duration-150 cursor-pointer">
                        <span>Promoted ({{ $promotedItems->count() }})</span>
                        <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="divide-y divide-hairline">
                        @foreach($promotedItems as $item)
                        <li class="flex items-center gap-3 px-6 py-3 opacity-60">
                            {{-- No checkbox for promoted items — they cannot be bulk-selected --}}
                            @if($canManage)
                            <div class="flex-shrink-0 w-4"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <span class="text-[13px] line-through text-muted">{{ $item->title }}</span>
                            </div>
                            @if($item->promotedTask)
                            <a href="{{ route('tasks.show', $item->promotedTask) }}"
                               class="text-[12px] font-medium text-accent hover:underline flex-shrink-0 whitespace-nowrap">→ Task #{{ $item->promotedTask->id }}</a>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- ── Create / Edit modal ─────────────────────────────────────────────── --}}
                <template x-teleport="body">
                    <div x-show="showModal" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">

                        <div class="absolute inset-0 bg-black/45" @click="close()"></div>

                        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] overflow-hidden"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.stop>

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                                <h3 class="text-[15px] font-semibold text-ink"
                                    x-text="editing ? 'Edit Backlog Item' : 'New Backlog Item'"></h3>
                                <button @click="close()"
                                        class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Create form --}}
                            <form x-show="!editing"
                                  action="{{ route('backlog.store', $project) }}"
                                  method="POST">
                                @csrf
                                <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Title <span class="text-[#b94040]">*</span></label>
                                        <input type="text" name="title" x-model="form.title" required maxlength="255"
                                               placeholder="e.g. User notification system"
                                               class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg placeholder:text-muted placeholder:italic focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                                        <textarea name="description" x-model="form.description" rows="4"
                                                  placeholder="Brief overview of the idea..."
                                                  class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg placeholder:text-muted placeholder:italic focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
                                    </div>
                                    @if($sprints->count() > 0)
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Sprint</label>
                                        <select name="sprint_id" x-model="form.sprint_id"
                                                class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                            <option value="">No sprint</option>
                                            @foreach($sprints as $sprint)
                                            <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Guide</label>
                                        <textarea name="guide" x-model="form.guide" rows="6"
                                                  placeholder="Detailed context, requirements, references..."
                                                  class="w-full px-3 py-2.5 text-[13px] text-ink font-mono bg-surface border border-line rounded-lg placeholder:text-muted placeholder:italic placeholder:font-sans focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
                                        <label class="inline-flex items-center gap-1.5 mt-2 text-[12px] text-muted hover:text-dim cursor-pointer transition-colors duration-150">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                            </svg>
                                            Import .md file
                                            <input type="file" accept=".md" @change="loadGuide($event)" class="hidden">
                                        </label>
                                        <span x-show="form.guideFilename" x-text="form.guideFilename" class="ml-2 text-[11px] text-muted"></span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-hairline bg-canvas">
                                    <button type="button" @click="close()"
                                            class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                                        Save Item
                                    </button>
                                </div>
                            </form>

                            {{-- Edit forms (one per item) --}}
                            <template x-if="editing">
                                <div>
                                    @foreach($backlogItems as $item)
                                    <form x-show="editing && editing.id === {{ $item->id }}"
                                          action="{{ route('backlog.update', [$project, $item]) }}"
                                          method="POST">
                                        @csrf @method('PATCH')
                                        <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Title <span class="text-[#b94040]">*</span></label>
                                                <input type="text" name="title" x-model="form.title" required maxlength="255"
                                                       class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                                                <textarea name="description" x-model="form.description" rows="4"
                                                          class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
                                            </div>
                                            @if($sprints->count() > 0)
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Sprint</label>
                                                <select name="sprint_id" x-model="form.sprint_id"
                                                        class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                                    <option value="">No sprint</option>
                                                    @foreach($sprints as $sprint)
                                                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @endif
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Guide</label>
                                                <textarea name="guide" x-model="form.guide" rows="6"
                                                          class="w-full px-3 py-2.5 text-[13px] text-ink font-mono bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
                                                <label class="inline-flex items-center gap-1.5 mt-2 text-[12px] text-muted hover:text-dim cursor-pointer transition-colors duration-150">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                                    </svg>
                                                    Import .md file
                                                    <input type="file" accept=".md" @change="loadGuide($event)" class="hidden">
                                                </label>
                                                <span x-show="form.guideFilename" x-text="form.guideFilename" class="ml-2 text-[11px] text-muted"></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-hairline bg-canvas">
                                            <button type="button" @click="close()"
                                                    class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                    class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                                                Update Item
                                            </button>
                                        </div>
                                    </form>
                                    @endforeach
                                </div>
                            </template>

                        </div>
                    </div>
                </template>

                {{-- ── Promote modal ───────────────────────────────────────────────────── --}}
                <template x-teleport="body">
                    <div x-show="showPromoteModal" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">

                        <div class="absolute inset-0 bg-black/45" @click="closePromote()"></div>

                        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] overflow-hidden"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.stop>

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#edf7f2] text-[#2e7d55]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-[15px] font-semibold text-ink">Promote to Task</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="fetchPromoteSuggestions()"
                                            :disabled="promoteAiLoading"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[12px] font-medium text-[#7c3aed] bg-[#f5f3ff] border border-[#e0d9f9] rounded-lg hover:bg-[#ede9fe] transition-colors duration-150 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" :class="promoteAiLoading ? 'animate-spin' : ''">
                                            <path d="M12 2.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM7.5 12a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM18.894 6.166a.75.75 0 0 0-1.06-1.06l-1.591 1.59a.75.75 0 1 0 1.06 1.061l1.591-1.59ZM21.75 12a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5H21a.75.75 0 0 1 .75.75ZM17.834 18.894a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 1 0-1.061 1.06l1.59 1.591ZM12 18a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-2.25A.75.75 0 0 1 12 18ZM7.758 17.303a.75.75 0 0 0-1.061-1.06l-1.591 1.59a.75.75 0 0 0 1.06 1.061l1.592-1.59ZM6 12a.75.75 0 0 1-.75.75H3a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 6 12ZM6.697 7.757a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 0 0-1.061 1.06l1.59 1.591Z"/>
                                        </svg>
                                        <span x-text="promoteAiLoading ? 'Thinking…' : 'Ask AI'"></span>
                                    </button>
                                    <button @click="closePromote(); promoteAiResult = null; promoteAiError = '';"
                                            class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Promote forms (one per item) --}}
                            <template x-if="promoting">
                                <div>
                                    @foreach($backlogItems as $item)
                                    <form x-show="promoting && promoting.id === {{ $item->id }}"
                                          action="{{ route('backlog.promote', [$project, $item]) }}"
                                          method="POST">
                                        @csrf
                                        <div class="px-6 py-5 space-y-4 max-h-[72vh] overflow-y-auto">

                                            {{-- Title --}}
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Title <span class="text-[#b94040]">*</span></label>
                                                <input type="text" name="title" x-model="promoteForm.title" required maxlength="255"
                                                       class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                            </div>

                                            {{-- Description --}}
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                                                <textarea name="description" x-model="promoteForm.description" rows="4"
                                                          class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
                                            </div>

                                            {{-- Type + Priority --}}
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Type <span class="text-[#b94040]">*</span></label>
                                                    <select name="type" x-model="promoteForm.type" required
                                                            class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                                        <option value="feature">Feature</option>
                                                        <option value="bug">Bug</option>
                                                        <option value="change">Change</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Priority <span class="text-[#b94040]">*</span></label>
                                                    <select name="priority" x-model="promoteForm.priority" required
                                                            class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                                        <option value="low">Low</option>
                                                        <option value="medium">Medium</option>
                                                        <option value="high">High</option>
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Weight picker --}}
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-2">Complexity <span class="text-[#b94040]">*</span></label>
                                                <input type="hidden" name="weight" :value="promoteForm.weight">
                                                <div class="flex gap-2">
                                                    @foreach([1 => 'Trivial', 2 => 'Simple', 3 => 'Medium', 4 => 'Complex', 5 => 'Very complex'] as $w => $label)
                                                    <button type="button"
                                                            @click="promoteForm.weight = {{ $w }}"
                                                            :class="promoteForm.weight === {{ $w }}
                                                                ? 'bg-ink text-white border-ink'
                                                                : 'bg-surface text-dim border-line hover:border-muted hover:text-ink'"
                                                            class="flex-1 flex flex-col items-center justify-center py-2.5 border rounded-lg transition-colors duration-150 cursor-pointer">
                                                        <span class="text-[16px] font-bold leading-none">{{ $w }}</span>
                                                        <span class="text-[10px] font-medium mt-1 leading-none opacity-70">{{ $label }}</span>
                                                    </button>
                                                    @endforeach
                                                </div>
                                            </div>

                                            {{-- Sprint --}}
                                            @if($sprints->count() > 0)
                                            <div>
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Sprint</label>
                                                <select name="sprint_id" x-model="promoteForm.sprint_id"
                                                        class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                                    <option value="">No sprint</option>
                                                    @foreach($sprints as $sprint)
                                                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @endif

                                            {{-- Assign to + Due date --}}
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Assign to</label>
                                                    <select name="assigned_to" x-model="promoteForm.assigned_to"
                                                            class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                                        <option value="">Unassigned</option>
                                                        @foreach($teamMembers as $member)
                                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Due date</label>
                                                    <input type="date" name="due_date" x-model="promoteForm.due_date"
                                                           class="w-full px-3 py-2.5 text-[14px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                                </div>
                                            </div>

                                            {{-- AI Suggestions panel --}}
                                            <div x-show="promoteAiResult || promoteAiError" x-cloak
                                                 class="rounded-xl border border-accent/20 bg-accent-light p-4 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-[11px] font-bold uppercase tracking-[0.05em] text-accent">AI Suggests</p>
                                                    <button type="button"
                                                            @click="applyAiSuggestion('type', promoteAiResult.type); applyAiSuggestion('priority', promoteAiResult.priority); applyAiSuggestion('weight', promoteAiResult.weight); applyAiSuggestion('description', promoteAiResult.description)"
                                                            x-show="promoteAiResult"
                                                            class="text-[11px] font-semibold text-accent hover:underline cursor-pointer">
                                                        Apply all
                                                    </button>
                                                </div>
                                                <div x-show="promoteAiError" class="text-[12px] text-[#b94040]" x-text="promoteAiError"></div>
                                                <div x-show="promoteAiResult" class="space-y-2.5">
                                                    {{-- Type --}}
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[12px] text-dim">Type: <span class="font-semibold text-ink capitalize" x-text="promoteAiResult?.type"></span></span>
                                                        <button type="button" @click="applyAiSuggestion('type', promoteAiResult.type)"
                                                                class="text-[11px] font-medium text-accent hover:underline cursor-pointer">Apply</button>
                                                    </div>
                                                    {{-- Priority --}}
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[12px] text-dim">Priority: <span class="font-semibold text-ink capitalize" x-text="promoteAiResult?.priority"></span></span>
                                                        <button type="button" @click="applyAiSuggestion('priority', promoteAiResult.priority)"
                                                                class="text-[11px] font-medium text-accent hover:underline cursor-pointer">Apply</button>
                                                    </div>
                                                    {{-- Weight + reason --}}
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="flex-1">
                                                            <span class="text-[12px] text-dim">Complexity: <span class="font-semibold text-ink" x-text="promoteAiResult?.weight + '/5'"></span></span>
                                                            <p class="text-[11px] text-muted mt-0.5 leading-relaxed" x-text="promoteAiResult?.weight_reason"></p>
                                                        </div>
                                                        <button type="button" @click="applyAiSuggestion('weight', promoteAiResult.weight)"
                                                                class="text-[11px] font-medium text-accent hover:underline cursor-pointer flex-shrink-0">Apply</button>
                                                    </div>
                                                    {{-- Estimated hours (informational only) --}}
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[12px] text-dim">Est. hours: <span class="font-semibold text-ink" x-text="promoteAiResult?.estimated_hours + 'h'"></span></span>
                                                    </div>
                                                    {{-- Description --}}
                                                    <div class="space-y-1">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[12px] text-dim font-medium">Refined description</span>
                                                            <button type="button" @click="applyAiSuggestion('description', promoteAiResult.description)"
                                                                    class="text-[11px] font-medium text-accent hover:underline cursor-pointer">Apply</button>
                                                        </div>
                                                        <p class="text-[12px] text-ink leading-relaxed" x-text="promoteAiResult?.description"></p>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-hairline bg-canvas">
                                            <button type="button" @click="closePromote()"
                                                    class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-[13px] font-medium text-white bg-[#2e7d55] hover:bg-[#256647] rounded-lg transition-colors duration-150 cursor-pointer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
                                                </svg>
                                                Promote to Task
                                            </button>
                                        </div>
                                    </form>
                                    @endforeach
                                </div>
                            </template>

                        </div>
                    </div>
                </template>

                {{-- ── AI Planning Panel ──────────────────────────────────────────────── --}}
                <div>
                    <div x-show="aiPlanOpen" x-cloak
                         class="fixed inset-0 z-[70] flex flex-col bg-white"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2">

                        {{-- Panel header --}}
                        <div class="flex items-center justify-between px-8 py-4 border-b border-hairline flex-shrink-0">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-accent" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM7.5 12a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM18.894 6.166a.75.75 0 0 0-1.06-1.06l-1.591 1.59a.75.75 0 1 0 1.06 1.061l1.591-1.59ZM21.75 12a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5H21a.75.75 0 0 1 .75.75ZM17.834 18.894a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 1 0-1.061 1.06l1.59 1.591ZM12 18a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-2.25A.75.75 0 0 1 12 18ZM7.758 17.303a.75.75 0 0 0-1.061-1.06l-1.591 1.59a.75.75 0 0 0 1.06 1.061l1.592-1.59ZM6 12a.75.75 0 0 1-.75.75H3a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 6 12ZM6.697 7.757a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 0 0-1.061 1.06l1.59 1.591Z"/>
                                </svg>
                                <h2 class="text-[16px] font-semibold text-ink">Plan with AI</h2>
                                {{-- Step indicator --}}
                                <div class="flex items-center gap-1.5 ml-2">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold transition-colors duration-200"
                                          :class="planStep >= 1 ? 'bg-accent text-white' : 'bg-surface text-muted'">1</span>
                                    <span class="w-8 h-px" :class="planStep >= 2 ? 'bg-accent' : 'bg-hairline'"></span>
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold transition-colors duration-200"
                                          :class="planStep >= 2 ? 'bg-accent text-white' : 'bg-surface text-muted'">2</span>
                                </div>
                            </div>
                            <button @click="aiPlanOpen = false"
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Step 1: Input --}}
                        <div x-show="planStep === 1" class="flex-1 overflow-y-auto">
                            <div class="max-w-3xl mx-auto px-8 py-8 space-y-6">
                                <div>
                                    <p class="text-[14px] text-muted mb-6">Describe what you want to build — paste rough notes, feature ideas, or requirements. The AI will organize them into a structured sprint plan.</p>

                                    {{-- Tabs --}}
                                    <div class="flex gap-0 border-b border-hairline mb-5">
                                        <button type="button"
                                                @click="planTab = 'paste'"
                                                :class="planTab === 'paste' ? 'border-b-2 border-accent text-accent font-semibold' : 'text-muted hover:text-dim'"
                                                class="px-4 py-2 text-[13px] -mb-px transition-colors duration-150 cursor-pointer">
                                            Paste Notes
                                        </button>
                                        @if($backlogItems->count() > 0)
                                        <button type="button"
                                                @click="planTab = 'backlog'"
                                                :class="planTab === 'backlog' ? 'border-b-2 border-accent text-accent font-semibold' : 'text-muted hover:text-dim'"
                                                class="px-4 py-2 text-[13px] -mb-px transition-colors duration-150 cursor-pointer">
                                            From Backlog
                                            @if($backlogItems->count() > 0)
                                            <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-surface border border-hairline rounded-full text-muted">{{ $backlogItems->count() }}</span>
                                            @endif
                                        </button>
                                        @endif
                                    </div>

                                    {{-- Paste notes tab --}}
                                    <div x-show="planTab === 'paste'">
                                        <textarea x-model="planNotes"
                                                  placeholder="e.g. We need to build a customer notification system. Users should be able to subscribe to email and SMS alerts. Admins can trigger bulk notifications. We also need a preference center. Later, add push notifications for mobile..."
                                                  rows="16"
                                                  class="w-full px-4 py-3 text-[14px] text-ink bg-surface border border-line rounded-xl placeholder:text-muted placeholder:text-[13px] focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none font-mono leading-relaxed"></textarea>
                                        <p class="text-[12px] text-muted mt-2">Be as detailed or rough as you like. The AI will do the heavy lifting.</p>
                                    </div>

                                    {{-- From backlog tab --}}
                                    @if($backlogItems->count() > 0)
                                    <div x-show="planTab === 'backlog'" class="space-y-2">
                                        <p class="text-[12px] text-muted mb-3">Select backlog items to include in the AI plan:</p>
                                        @foreach($backlogItems as $item)
                                        <label class="flex items-start gap-3 p-3 rounded-lg border border-line hover:border-accent hover:bg-accent-light transition-colors duration-150 cursor-pointer"
                                               :class="planSelectedItems.includes({{ $item->id }}) ? 'border-accent bg-accent-light' : ''">
                                            <input type="checkbox"
                                                   @click="togglePlanItem({{ $item->id }})"
                                                   :checked="planSelectedItems.includes({{ $item->id }})"
                                                   class="mt-0.5 w-4 h-4 rounded border-line cursor-pointer accent-[#D97757]">
                                            <div>
                                                <p class="text-[13px] font-medium text-ink">{{ $item->title }}</p>
                                                @if($item->description)
                                                <p class="text-[12px] text-muted mt-0.5">{{ Str::limit($item->description, 120) }}</p>
                                                @endif
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                    @endif

                                    {{-- Error --}}
                                    <div x-show="planError" class="mt-4 flex items-center gap-2 text-[13px] text-[#b94040] bg-[#fff5f5] border border-[#f5c6c6] rounded-lg px-4 py-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                                        </svg>
                                        <span x-text="planError"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Review --}}
                        <div x-show="planStep === 2" class="flex-1 overflow-y-auto">
                            <div class="max-w-4xl mx-auto px-8 py-8">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <h3 class="text-[15px] font-semibold text-ink">Review your plan</h3>
                                        <p class="text-[13px] text-muted mt-0.5">Edit sprint names, remove items, or reorganize before creating.</p>
                                    </div>
                                    <button type="button" @click="addSprint()"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                        + Add sprint
                                    </button>
                                </div>

                                <div class="space-y-5">
                                    <template x-for="(sprint, sIdx) in planResult?.sprints ?? []" :key="sIdx">
                                        <div class="border border-line rounded-xl overflow-hidden">
                                            {{-- Sprint header --}}
                                            <div class="flex items-center gap-3 px-5 py-3.5 bg-canvas border-b border-hairline">
                                                <input type="text" x-model="sprint.name"
                                                       class="flex-1 text-[14px] font-semibold text-ink bg-transparent border-0 focus:outline-none focus:ring-0 placeholder:text-muted"
                                                       placeholder="Sprint name">
                                                <button type="button" @click="removeSprint(sIdx)"
                                                        title="Remove sprint"
                                                        class="w-6 h-6 flex items-center justify-center rounded text-muted hover:text-[#b94040] hover:bg-[#fff0f0] transition-colors duration-150 cursor-pointer flex-shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div x-show="sprint.rationale" class="px-5 py-2 text-[12px] text-muted bg-canvas border-b border-hairline" x-text="sprint.rationale"></div>
                                            {{-- Items --}}
                                            <ul class="divide-y divide-hairline">
                                                <template x-for="(item, iIdx) in sprint.items" :key="iIdx">
                                                    <li class="flex items-start gap-3 px-5 py-3">
                                                        <div class="flex-1 min-w-0">
                                                            <input type="text" x-model="item.title"
                                                                   class="w-full text-[13px] font-medium text-ink bg-transparent border-0 focus:outline-none focus:ring-0 placeholder:text-muted"
                                                                   placeholder="Item title">
                                                            <p class="text-[12px] text-muted mt-0.5 truncate" x-text="item.description"></p>
                                                        </div>
                                                        <div class="flex items-center gap-1.5 flex-shrink-0 mt-0.5">
                                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                                                  :class="{
                                                                      'bg-[#eef3fb] text-[#3a6fba]': item.type === 'feature',
                                                                      'bg-[#fff0f0] text-[#b94040]': item.type === 'bug',
                                                                      'bg-surface text-muted': item.type === 'change'
                                                                  }"
                                                                  x-text="item.type"></span>
                                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-canvas border border-hairline text-dim"
                                                                  x-text="'W' + (item.weight ?? 3)"></span>
                                                            {{-- Move to sprint --}}
                                                            <template x-if="planResult.sprints.length > 1">
                                                                <select @change="moveItem(sIdx, iIdx, parseInt($event.target.value)); $event.target.value = ''"
                                                                        class="text-[11px] text-muted bg-surface border border-hairline rounded px-1.5 py-0.5 cursor-pointer focus:outline-none focus:border-accent">
                                                                    <option value="" disabled selected>Move →</option>
                                                                    <template x-for="(s2, s2Idx) in planResult.sprints" :key="s2Idx">
                                                                        <option :value="s2Idx" x-text="s2.name" :disabled="s2Idx === sIdx"></option>
                                                                    </template>
                                                                </select>
                                                            </template>
                                                            <button type="button" @click="removeItem(sIdx, iIdx)"
                                                                    class="w-5 h-5 flex items-center justify-center rounded text-muted hover:text-[#b94040] hover:bg-[#fff0f0] transition-colors duration-150 cursor-pointer">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </li>
                                                </template>
                                                <li x-show="sprint.items.length === 0" class="px-5 py-3 text-[12px] text-muted italic">No items in this sprint.</li>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Panel footer --}}
                        <div class="flex items-center justify-between px-8 py-4 border-t border-hairline bg-canvas flex-shrink-0">
                            <button type="button"
                                    @click="planStep > 1 ? planStep-- : (aiPlanOpen = false)"
                                    class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                <span x-text="planStep > 1 ? '← Back' : 'Cancel'"></span>
                            </button>

                            {{-- Step 1 footer: Analyze button --}}
                            <div x-show="planStep === 1">
                                <button type="button"
                                        @click="analyzePlan()"
                                        :disabled="planLoading"
                                        class="inline-flex items-center gap-2 px-5 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                    <svg x-show="planLoading" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-text="planLoading ? 'Analyzing…' : 'Analyze with AI →'"></span>
                                </button>
                            </div>

                            {{-- Step 2 footer: Confirm button --}}
                            <div x-show="planStep === 2">
                                <form method="POST" action="{{ route('ai.plan.confirm', $project) }}" x-ref="confirmPlanForm">
                                    @csrf
                                    <input type="hidden" name="raw_input" :value="planRawInput">
                                    <input type="hidden" name="sprints" :value="JSON.stringify(planResult?.sprints ?? [])">
                                    <button type="button"
                                            @click="
                                                $refs.confirmPlanForm.querySelector('[name=sprints]').value = JSON.stringify(planResult?.sprints ?? []);
                                                $refs.confirmPlanForm.submit();
                                            "
                                            :disabled="!planResult?.sprints?.length"
                                            class="inline-flex items-center gap-2 px-5 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                        Create everything
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            @endif

            {{-- Recent tasks (managers) / My Tasks + Available to Claim (developers) --}}
            @if($canManage)
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
                    @if($project->tasks->count() > 5)
                    <div class="px-6 py-3 border-t border-hairline">
                        <a href="{{ route('tasks.index', ['project' => $project->id]) }}"
                           class="text-[12px] font-medium text-accent hover:underline">
                            View all {{ $project->tasks->count() }} tasks →
                        </a>
                    </div>
                    @endif
                @endif
            </div>
            @else
            {{-- ── Developer: Available to Claim ────────────────────────────────── --}}
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
                        <li class="flex items-start gap-3 px-6 py-3.5 hover:bg-canvas transition-colors">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($task->weight)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-surface text-muted border border-hairline"
                                          title="Complexity weight">{{ $task->weight }}</span>
                                    @endif
                                    <button type="button"
                                            onclick="window.dispatchEvent(new CustomEvent('open-task', { detail: { id: {{ $task->id }} } }))"
                                            class="text-[13.5px] font-medium text-ink hover:text-accent transition-colors cursor-pointer text-left">{{ $task->title }}</button>
                                    @if($task->type)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-surface text-dim">{{ ucfirst($task->type) }}</span>
                                    @endif
                                </div>
                                @php
                                    $pColors = ['critical' => ['#fdf0f0','#b94040'], 'high' => ['#fdf0f0','#b94040'], 'medium' => ['#fef9ec','#9a7a1a'], 'low' => ['#edf7f2','#2e7d55']];
                                    $pc = $pColors[$task->priority] ?? ['#F5F4EF','#8c8c8a'];
                                @endphp
                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[11px] font-semibold"
                                      style="background:{{ $pc[0] }};color:{{ $pc[1] }}">{{ ucfirst($task->priority) }}</span>
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

            {{-- ── Developer: My Tasks on this project ──────────────────────────── --}}
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

        {{-- Right column (40%) --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Project details --}}
            <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <div class="px-5 py-4 border-b border-hairline">
                    <h2 class="text-[13px] font-semibold text-ink">Project Details</h2>
                </div>
                <dl class="divide-y divide-hairline">
                    @if($canManage)
                    <div class="px-5 py-3">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-muted mb-0.5">Budget</dt>
                        <dd class="text-[13px] text-ink">{{ $project->budget ? number_format($project->budget, 2) . ' MRU' : '—' }}</dd>
                    </div>
                    @endif
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

                {{-- Card header --}}
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

                {{-- Team list --}}
                @if($assignedTeams->isEmpty())
                    <div class="px-5 py-6 text-center">
                        <p class="text-[13px] text-muted">No teams assigned yet</p>
                        @if($canManage)
                        <button @click="open = true"
                                class="inline-block mt-2 text-[12px] font-medium text-accent hover:underline cursor-pointer">
                            Assign a team →
                        </button>
                        @endif
                    </div>
                @else
                    <ul class="divide-y divide-hairline">
                        @foreach($assignedTeams as $team)
                        <li class="flex items-center gap-3 px-5 py-3">
                            {{-- Team icon --}}
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

                {{-- Team picker modal --}}
                @if($canManage)
                <template x-teleport="body">
                    <div x-show="open" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">

                        <div class="absolute inset-0 bg-black/45" @click="open = false"></div>

                        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] overflow-hidden"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.stop>

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                                <h3 class="text-[15px] font-semibold text-ink">Assign Teams</h3>
                                <button @click="open = false"
                                        class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Team checkboxes --}}
                            <form action="{{ route('projects.assign-teams', $project) }}" method="POST">
                                @csrf
                                @if($allTeams->isEmpty())
                                <div class="px-6 py-8 text-center">
                                    <p class="text-[13px] text-muted">No teams exist yet.</p>
                                </div>
                                @else
                                <div class="px-3 py-3 space-y-1 max-h-72 overflow-y-auto">
                                    @foreach($allTeams as $team)
                                    @php $isAssigned = in_array($team->id, $assignedTeamIds); @endphp
                                    <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-canvas cursor-pointer transition-colors duration-100">
                                        <input type="checkbox"
                                               name="teams[]"
                                               value="{{ $team->id }}"
                                               @checked($isAssigned)
                                               class="w-4 h-4 rounded border-line cursor-pointer accent-[#D97757]">
                                        <span class="flex-1 text-[13px] font-medium text-ink">{{ $team->name }}</span>
                                        @if($isAssigned)
                                        <span class="text-[11px] font-semibold text-[#2e7d55] bg-[#edf7f2] px-2 py-0.5 rounded-full">Assigned</span>
                                        @endif
                                    </label>
                                    @endforeach
                                </div>
                                @endif

                                {{-- Footer --}}
                                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-hairline">
                                    <button type="button" @click="open = false"
                                            class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                                        Save
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </template>
                @endif

            </div>

            {{-- Guide card --}}
            <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-hairline">
                    <h2 class="text-[13px] font-semibold text-ink">Project Guide</h2>
                    @if($project->guide && $canManage)
                    <a href="{{ route('projects.edit', $project) }}"
                       class="text-[12px] text-accent hover:underline">Edit</a>
                    @endif
                </div>

                @if($project->guide)
                <div x-data="{ expanded: false }" class="px-5 py-4">
                    <pre class="text-[12px] font-mono text-dim whitespace-pre-wrap break-words leading-relaxed"
                         x-show="!expanded">{{ mb_substr($project->guide, 0, 300) }}{{ mb_strlen($project->guide) > 300 ? '…' : '' }}</pre>
                    <pre class="text-[12px] font-mono text-dim whitespace-pre-wrap break-words leading-relaxed"
                         x-show="expanded" x-cloak>{{ $project->guide }}</pre>
                    @if(mb_strlen($project->guide) > 300)
                    <button @click="expanded = !expanded"
                            class="mt-3 text-[12px] font-medium text-accent hover:underline focus:outline-none"
                            x-text="expanded ? 'Show less' : 'View full guide'"></button>
                    @endif
                </div>
                @else
                <div class="px-5 py-6 text-center">
                    <p class="text-[13px] text-muted">No guide yet</p>
                    @if($canManage)
                    <a href="{{ route('projects.edit', $project) }}"
                       class="inline-block mt-2 text-[12px] font-medium text-accent hover:underline">Add a guide →</a>
                    @endif
                </div>
                @endif
            </div>

        </div>
    </div>

</div>

@if(! $canManage)
<livewire:task-modal />
<script>
    window.addEventListener('task-claimed', () => window.location.reload());
</script>
@endif

</x-layouts.app>

