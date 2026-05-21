<div class="space-y-4">
    {{-- Flash inline (for Livewire actions) --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
         class="flex items-center gap-2 px-4 py-3 rounded-xl border text-[13px] font-medium"
         style="background:#edf7f2;border-color:#b7e0ca;color:#2e7d55">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="flex items-center gap-2 px-4 py-3 rounded-xl border text-[13px] font-medium"
         style="background:#fff5f5;border-color:#f5c6c6;color:#b94040">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-[16px] font-semibold text-ink">Sprints & Tasks</h2>
        @if($canManage)
        <button wire:click="$set('showAddSprint', true)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-ink bg-white border border-line rounded-lg hover:bg-canvas transition-colors duration-150 cursor-pointer shadow-[0_1px_2px_rgba(20,20,19,0.04)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Sprint
        </button>
        @endif
    </div>

    {{-- Add Sprint inline form --}}
    @if($showAddSprint && $canManage)
    <div class="bg-white border border-accent/30 rounded-xl shadow-[0_2px_8px_rgba(20,20,19,0.06)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline bg-canvas">
            <p class="text-[13px] font-semibold text-ink">New Sprint</p>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Sprint Name <span class="text-[#b94040]">*</span></label>
                <input wire:model="addSprintName" type="text" placeholder="e.g. Sprint 1 — Core Auth"
                       class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                @error('addSprintName')<p class="text-[12px] text-[#b94040] mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                    <input wire:model="addSprintDescription" type="text" placeholder="Optional"
                           class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Deadline</label>
                    <input wire:model="addSprintDeadline" type="date"
                           class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-hairline bg-canvas">
            <button wire:click="$set('showAddSprint', false)"
                    class="px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                Cancel
            </button>
            <button wire:click="createSprint" wire:loading.attr="disabled"
                    class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer disabled:opacity-60">
                Create Sprint
            </button>
        </div>
    </div>
    @endif

    {{-- Sprint list --}}
    @forelse($sprints as $sprint)
    @php
        $isExpanded = in_array($sprint->id, $expandedSprints);
        $isEditingSprint = $editingSprint === $sprint->id;
        $statusColors = [
            'draft'     => ['dot' => '#8c8c8a', 'bg' => '#F5F4EF', 'text' => '#5c5c5a', 'label' => 'Draft'],
            'active'    => ['dot' => '#2e7d55', 'bg' => '#edf7f2', 'text' => '#2e7d55', 'label' => 'Active'],
            'completed' => ['dot' => '#3a6fba', 'bg' => '#eef3fb', 'text' => '#3a6fba', 'label' => 'Completed'],
        ];
        $sc = $statusColors[$sprint->status] ?? $statusColors['draft'];
        $doneTasks = $sprint->tasks->where('status', 'done')->count();
        $totalTasks = $sprint->tasks->count();
        $progress = $sprint->progress_percent;
    @endphp
    <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">

        {{-- Sprint header --}}
        @if($isEditingSprint)
        {{-- Inline edit form --}}
        <div class="px-5 py-4 space-y-3 bg-canvas border-b border-hairline">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Sprint Name <span class="text-[#b94040]">*</span></label>
                <input wire:model="editSprintName" type="text"
                       class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                @error('editSprintName')<p class="text-[12px] text-[#b94040] mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                    <input wire:model="editSprintDescription" type="text"
                           class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Deadline</label>
                    <input wire:model="editSprintDeadline" type="date"
                           class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button wire:click="cancelEditSprint"
                        class="px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                    Cancel
                </button>
                <button wire:click="saveSprint"
                        class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                    Save
                </button>
            </div>
        </div>
        @else
        {{-- Normal header --}}
        <div class="flex items-center gap-3 px-5 py-3.5 cursor-pointer select-none hover:bg-canvas transition-colors duration-100"
             wire:click="toggleSprint({{ $sprint->id }})">
            {{-- Status dot --}}
            <span class="flex-shrink-0 w-2 h-2 rounded-full" style="background: {{ $sc['dot'] }}"></span>
            {{-- Name --}}
            <span class="flex-1 text-[14px] font-semibold text-ink truncate">{{ $sprint->name }}</span>
            {{-- Status badge --}}
            <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
                  style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">{{ $sc['label'] }}</span>
            {{-- Deadline --}}
            @if($sprint->deadline)
            <span class="flex-shrink-0 text-[12px] {{ $sprint->deadline->isPast() && $sprint->status !== 'completed' ? 'text-[#b94040] font-medium' : 'text-muted' }}">
                Due {{ $sprint->deadline->format('M d') }}
            </span>
            @endif
            {{-- Task count --}}
            <span class="flex-shrink-0 text-[12px] text-muted">{{ $doneTasks }}/{{ $totalTasks }} done</span>
            {{-- Progress bar --}}
            <div class="flex-shrink-0 w-16 h-1.5 rounded-full bg-hairline overflow-hidden hidden sm:block">
                <div class="h-full rounded-full bg-accent" style="width: {{ $progress }}%"></div>
            </div>
            {{-- Actions (stop propagation to prevent toggle) --}}
            @if($canManage)
            <div class="flex items-center gap-0.5 flex-shrink-0 ml-1" @click.stop>
                {{-- Edit --}}
                <button wire:click="editSprint({{ $sprint->id }})"
                        class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer"
                        title="Edit sprint">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                    </svg>
                </button>
                {{-- Publish / Unpublish / Complete --}}
                @if($sprint->status === 'draft')
                <form action="{{ route('sprints.publish', [$project, $sprint]) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="h-7 px-2 text-[11px] font-semibold rounded-lg text-[#2e7d55] hover:bg-[#edf7f2] transition-colors duration-150 cursor-pointer"
                            title="Publish sprint">
                        Publish
                    </button>
                </form>
                @elseif($sprint->status === 'active')
                <form action="{{ route('sprints.unpublish', [$project, $sprint]) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="h-7 px-2 text-[11px] font-medium rounded-lg text-muted hover:bg-hairline transition-colors duration-150 cursor-pointer"
                            title="Move back to draft">
                        Unpublish
                    </button>
                </form>
                <form action="{{ route('sprints.complete', [$project, $sprint]) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="h-7 px-2 text-[11px] font-semibold rounded-lg text-[#3a6fba] hover:bg-[#eef3fb] transition-colors duration-150 cursor-pointer"
                            title="Mark as completed">
                        Complete
                    </button>
                </form>
                @endif
                {{-- Delete --}}
                <button wire:click="deleteSprint({{ $sprint->id }})"
                        wire:confirm="Delete this sprint? Tasks inside will also be deleted."
                        class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-[#b94040] hover:bg-[#fff0f0] transition-colors duration-150 cursor-pointer"
                        title="Delete sprint">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                </button>
            </div>
            @endif
            {{-- Expand chevron --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted flex-shrink-0 transition-transform duration-200 {{ $isExpanded ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
        </div>
        @if($sprint->description)
        <p class="px-5 pb-2 text-[12px] text-muted" wire:click="toggleSprint({{ $sprint->id }})">{{ $sprint->description }}</p>
        @endif
        @endif

        {{-- Progress bar (full width) --}}
        @if($totalTasks > 0)
        <div class="h-0.5 bg-hairline">
            <div class="h-full bg-accent transition-all duration-300" style="width: {{ $progress }}%"></div>
        </div>
        @endif

        {{-- Sprint body (tasks) --}}
        @if($isExpanded)
        <div class="border-t border-hairline">
            {{-- Tasks --}}
            @if($sprint->tasks->isEmpty() && !$addTaskSprintId)
            <div class="px-5 py-8 text-center">
                <p class="text-[13px] text-muted">No tasks in this sprint yet.</p>
                @if($canManage)
                <button wire:click="showAddTask({{ $sprint->id }})"
                        class="mt-2 text-[12px] font-medium text-accent hover:underline cursor-pointer">
                    Add the first task →
                </button>
                @endif
            </div>
            @else
            {{-- Column header --}}
            <div class="flex items-center gap-3 px-5 py-2 bg-canvas border-b border-hairline">
                <span class="w-7 text-[10px] font-bold uppercase tracking-wider text-muted text-center flex-shrink-0">W</span>
                <span class="flex-1 text-[10px] font-bold uppercase tracking-wider text-muted">Title</span>
                <span class="w-16 text-[10px] font-bold uppercase tracking-wider text-muted text-center flex-shrink-0 hidden lg:block">Type</span>
                <span class="w-16 text-[10px] font-bold uppercase tracking-wider text-muted text-center flex-shrink-0 hidden sm:block">Priority</span>
                <span class="w-20 text-[10px] font-bold uppercase tracking-wider text-muted flex-shrink-0">Status</span>
                <span class="w-24 text-[10px] font-bold uppercase tracking-wider text-muted flex-shrink-0 hidden md:block">Assignee</span>
                <span class="w-20 text-[10px] font-bold uppercase tracking-wider text-muted flex-shrink-0 hidden lg:block">Due</span>
                <span class="w-10 text-[10px] font-bold uppercase tracking-wider text-muted text-center flex-shrink-0 hidden xl:block">✓</span>
                @if($canManage)<span class="w-7 flex-shrink-0"></span>@endif
            </div>

            {{-- Task rows --}}
            @foreach($sprint->tasks->sortBy('sort_order') as $task)
            @if($editingTask === $task->id)
            {{-- Inline edit row --}}
            <div class="px-5 py-3 bg-canvas border-b border-hairline space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <input wire:model="editTaskTitle" type="text"
                               class="w-full px-3 py-1.5 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150"
                               placeholder="Task title">
                        @error('editTaskTitle')<p class="text-[12px] text-[#b94040] mt-0.5">{{ $message }}</p>@enderror
                    </div>
                    <select wire:model="editTaskStatus"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer">
                        @foreach($columns as $col)
                        <option value="{{ $col->slug }}">{{ $col->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model="editTaskPriority"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                    <select wire:model="editTaskAssignedTo"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer">
                        <option value="">Unassigned</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="editTaskDueDate" type="date"
                           class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15">
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] text-muted">W:</span>
                        <input wire:model="editTaskWeight" type="number" min="1" max="5"
                               class="w-12 px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 text-center">
                    </div>
                </div>
                <div class="flex items-center gap-2 justify-end">
                    <button wire:click="cancelEdit"
                            class="px-3 py-1 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                        Cancel
                    </button>
                    <button wire:click="saveTask"
                            class="px-3 py-1 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                        Save
                    </button>
                </div>
            </div>
            @else
            {{-- Normal task row --}}
            @php
                $pColors = [
                    'critical' => ['#fdf0f0','#b94040'],
                    'high'     => ['#fdf0f0','#b94040'],
                    'medium'   => ['#fef9ec','#9a7a1a'],
                    'low'      => ['#edf7f2','#2e7d55'],
                ];
                $pc = $pColors[$task->priority] ?? ['#F5F4EF','#8c8c8a'];
                $tColors = [
                    'bug'     => ['#fff0f0','#b94040'],
                    'feature' => ['#eef3fb','#3a6fba'],
                    'change'  => ['#F5F4EF','#5c5c5a'],
                ];
                $tc = $tColors[$task->type ?? 'change'] ?? ['#F5F4EF','#5c5c5a'];
                $checklistDone = $task->checklists->where('completed', true)->count();
                $checklistTotal = $task->checklists->count();
            @endphp
            <div class="flex items-center gap-3 px-5 py-2.5 hover:bg-canvas transition-colors duration-100 border-b border-hairline last:border-b-0">
                {{-- Weight --}}
                <span class="flex-shrink-0 w-7 h-6 flex items-center justify-center rounded text-[10px] font-bold bg-surface text-muted border border-hairline text-center">
                    {{ $task->weight ?? '—' }}
                </span>
                {{-- Title --}}
                <button wire:click="openTask({{ $task->id }})"
                        class="flex-1 text-[13px] font-medium text-ink hover:text-accent transition-colors text-left truncate cursor-pointer">
                    {{ $task->title }}
                </button>
                {{-- Type --}}
                @if($task->type)
                <span class="flex-shrink-0 w-16 text-center hidden lg:inline-flex justify-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium"
                          style="background:{{ $tc[0] }};color:{{ $tc[1] }}">{{ ucfirst($task->type) }}</span>
                </span>
                @else
                <span class="w-16 flex-shrink-0 hidden lg:block"></span>
                @endif
                {{-- Priority --}}
                <span class="flex-shrink-0 w-16 text-center hidden sm:inline-flex justify-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                          style="background:{{ $pc[0] }};color:{{ $pc[1] }}">{{ ucfirst($task->priority) }}</span>
                </span>
                {{-- Status --}}
                <span class="flex-shrink-0 w-20">
                    @include('components.badge', ['type' => 'status', 'value' => $task->status])
                </span>
                {{-- Assignee --}}
                <span class="flex-shrink-0 w-24 hidden md:flex items-center gap-1.5">
                    @if($task->assignee)
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-semibold text-white flex-shrink-0"
                          style="background: {{ $task->assignee->color ?? '#8c8c8a' }}"
                          title="{{ $task->assignee->name }}">
                        {{ $task->assignee->initials ?? mb_strtoupper(mb_substr($task->assignee->name, 0, 2)) }}
                    </span>
                    <span class="text-[12px] text-muted truncate">{{ $task->assignee->name }}</span>
                    @else
                    <span class="text-[12px] text-muted">—</span>
                    @endif
                </span>
                {{-- Due date --}}
                <span class="flex-shrink-0 w-20 text-[12px] hidden lg:block {{ $task->due_date && $task->due_date->isPast() && $task->status !== 'done' ? 'text-[#b94040] font-medium' : 'text-muted' }}">
                    {{ $task->due_date?->format('M d, Y') ?? '—' }}
                </span>
                {{-- Checklist --}}
                <span class="flex-shrink-0 w-10 text-center hidden xl:block">
                    @if($checklistTotal > 0)
                    <span class="text-[11px] {{ $checklistDone === $checklistTotal ? 'text-[#2e7d55] font-semibold' : 'text-muted' }}">{{ $checklistDone }}/{{ $checklistTotal }}</span>
                    @endif
                </span>
                {{-- Edit action --}}
                @if($canManage)
                <button wire:click="editTask({{ $task->id }})"
                        class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer"
                        title="Edit task">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                    </svg>
                </button>
                @endif
            </div>
            @endif
            @endforeach
            @endif

            {{-- Quick add task form --}}
            @if($addTaskSprintId === $sprint->id && $canManage)
            <div class="px-5 py-3 bg-canvas border-t border-hairline space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <input wire:model="addTaskTitle" type="text"
                               placeholder="Task title..."
                               class="w-full px-3 py-1.5 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                        @error('addTaskTitle')<p class="text-[12px] text-[#b94040] mt-0.5">{{ $message }}</p>@enderror
                    </div>
                    <select wire:model="addTaskType"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer">
                        <option value="feature">Feature</option>
                        <option value="bug">Bug</option>
                        <option value="change">Change</option>
                    </select>
                    <select wire:model="addTaskPriority"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] text-muted">W:</span>
                        <input wire:model="addTaskWeight" type="number" min="1" max="5"
                               class="w-12 px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 text-center">
                    </div>
                </div>
                <div class="flex items-center gap-2 justify-end">
                    <button wire:click="cancelAddTask"
                            class="px-3 py-1 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                        Cancel
                    </button>
                    <button wire:click="createTask"
                            class="px-3 py-1 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                        Add Task
                    </button>
                </div>
            </div>
            @elseif($canManage)
            <div class="px-5 py-2.5 border-t border-hairline">
                <button wire:click="showAddTask({{ $sprint->id }})"
                        class="inline-flex items-center gap-1 text-[12px] font-medium text-muted hover:text-accent transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add task to this sprint
                </button>
            </div>
            @endif
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white border border-line rounded-xl px-6 py-12 text-center shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <svg class="w-10 h-10 text-muted mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
        </svg>
        <p class="text-[14px] font-medium text-dim">No sprints yet</p>
        <p class="text-[13px] text-muted mt-1">Create a sprint to start organizing tasks.</p>
        @if($canManage)
        <button wire:click="$set('showAddSprint', true)"
                class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
            + New Sprint
        </button>
        @endif
    </div>
    @endforelse

    {{-- Livewire loading indicator --}}
    <div wire:loading.delay class="fixed bottom-4 right-4 z-50 flex items-center gap-2 px-3 py-2 bg-white border border-line rounded-lg shadow-lg text-[12px] text-muted">
        <svg class="w-4 h-4 animate-spin text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Saving…
    </div>
</div>
