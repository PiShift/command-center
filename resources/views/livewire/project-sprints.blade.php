@php
    $completeUrlTemplate = route('sprints.complete', [$project, '__SPRINT__']);
    $sprintChoices = $sprints->map(fn ($item) => [
        'id' => (int) $item->id,
        'name' => $item->name,
        'status' => $item->status,
    ])->values();
@endphp

<div class="space-y-4"
     x-data="{
    sectionOpen: { active: true, draft: true, completed: false },
        completeModalOpen: false,
        completeSprintId: null,
        completeSprintName: '',
        unfinishedCount: 0,
        completeAction: 'move_existing',
        targetSprintId: '',
        newSprintName: '',
        completeUrlTemplate: @js($completeUrlTemplate),
        sprintChoices: @js($sprintChoices),
        openCompleteModal(id, name, count) {
            this.completeSprintId = id;
            this.completeSprintName = name;
            this.unfinishedCount = count;
            this.completeAction = 'move_existing';
            this.targetSprintId = '';
            this.newSprintName = '';
            this.completeModalOpen = true;
        },
        availableTargetSprints() {
            return this.sprintChoices.filter((s) => s.id !== this.completeSprintId && (s.status === 'draft' || s.status === 'active'));
        },
        canSubmitCompletion() {
            if (this.completeAction === 'move_existing') {
                return this.targetSprintId !== '';
            }
            if (this.completeAction === 'create_new') {
                return this.newSprintName.trim().length > 0;
            }
            return this.completeAction === 'complete_anyway';
        }
     }">
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
    @php
        $activeCount = $sprints->where('status', 'active')->count();
        $draftCount = $sprints->where('status', 'draft')->count();
        $completedCount = $sprints->where('status', 'completed')->count();
        $sectionPrinted = ['active' => false, 'draft' => false, 'completed' => false];
    @endphp
    @forelse($sprints as $sprint)
    @php
        $isExpanded = in_array($sprint->id, $expandedSprints);
        $isEditingSprint = $editingSprint === $sprint->id;
        $selectedInSprint = collect($selectedTaskIds ?? [])->map(fn ($id) => (int) $id)->intersect($sprint->tasks->pluck('id'))->count();
        $statusColors = [
            'draft'     => ['dot' => '#8c8c8a', 'bg' => '#F5F4EF', 'text' => '#5c5c5a', 'label' => 'Draft'],
            'active'    => ['dot' => '#2e7d55', 'bg' => '#edf7f2', 'text' => '#2e7d55', 'label' => 'Active'],
            'completed' => ['dot' => '#3a6fba', 'bg' => '#eef3fb', 'text' => '#3a6fba', 'label' => 'Completed'],
        ];
        $sc = $statusColors[$sprint->status] ?? $statusColors['draft'];
        $doneTasks = $sprint->tasks->where('status', 'done')->count();
        $totalTasks = $sprint->tasks->count();
        $unfinishedTasks = $sprint->tasks->where('status', '!=', 'done')->count();
        $progress = $sprint->progress_percent;
    @endphp
    @if(! $sectionPrinted[$sprint->status])
    <button type="button"
            @click="sectionOpen.{{ $sprint->status }} = !sectionOpen.{{ $sprint->status }}"
            class="w-full flex items-center justify-between px-4 py-2 rounded-lg border border-line bg-canvas cursor-pointer hover:bg-hairline transition-colors">
        <div class="flex items-center gap-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">
                {{ $sprint->status === 'active' ? 'Active' : ($sprint->status === 'draft' ? 'Draft' : 'Completed') }}
            </span>
            <span class="text-[11px] text-muted">
                {{ $sprint->status === 'active' ? $activeCount : ($sprint->status === 'draft' ? $draftCount : $completedCount) }}
            </span>
            @if($sprint->status === 'completed')
            <span class="text-[11px] text-muted" x-show="!sectionOpen.completed">completed sprints — click to expand</span>
            @endif
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted transition-transform"
             :class="sectionOpen.{{ $sprint->status }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>
    @php $sectionPrinted[$sprint->status] = true; @endphp
    @endif

    <div x-show="sectionOpen.{{ $sprint->status }}">
    <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">

        {{-- Sprint header --}}
        @if($isEditingSprint)
        {{-- Inline edit form --}}
        <div class="px-5 py-4 space-y-3 bg-canvas border-b border-hairline">
            <div>
            </div>
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
                @if($unfinishedTasks > 0)
                <button type="button"
                        @click="openCompleteModal({{ $sprint->id }}, @js($sprint->name), {{ $unfinishedTasks }})"
                        class="h-7 px-2 text-[11px] font-semibold rounded-lg text-[#3a6fba] hover:bg-[#eef3fb] transition-colors duration-150 cursor-pointer"
                        title="Mark as completed">
                    Complete
                </button>
                @else
                <form action="{{ route('sprints.complete', [$project, $sprint]) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="h-7 px-2 text-[11px] font-semibold rounded-lg text-[#3a6fba] hover:bg-[#eef3fb] transition-colors duration-150 cursor-pointer"
                            title="Mark as completed">
                        Complete
                    </button>
                </form>
                @endif
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
                @if($canManage)
                <span class="w-6 flex-shrink-0"></span>
                @endif
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
                    <select wire:model="editTaskSprintId"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer">
                        <option value="">No sprint</option>
                        @foreach($allProjectSprints as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst($s->status) }})</option>
                        @endforeach
                    </select>
                    <select wire:model="editTaskStatus"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer {{ $errors->has('editTaskStatus') ? 'border-[#b94040]' : 'border-line' }}">
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
                @error('editTaskStatus')
                <div class="flex items-start gap-1.5 text-[12px] text-[#b94040] bg-[#fff5f5] border border-[#f5c6c6] rounded-lg px-3 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <span>{{ $message }}</span>
                </div>
                @enderror
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
                @if($canManage)
                <span class="flex-shrink-0 w-6 flex items-center justify-center">
                    <input type="checkbox" wire:model="selectedTaskIds" value="{{ $task->id }}"
                           class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30 cursor-pointer">
                </span>
                @endif
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

            @if($canManage)
            <div x-data="{ show: {{ $selectedInSprint > 0 ? 'true' : 'false' }} }"
                 x-show="show" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="px-5 py-3 bg-canvas border-t border-hairline">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[12px] font-semibold text-ink">{{ $selectedInSprint }} tasks selected</span>
                    <select wire:model="bulkMoveTargetBySprint.{{ $sprint->id }}"
                            class="px-2 py-1.5 text-[12px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/15 cursor-pointer">
                        <option value="">Move to sprint...</option>
                        @foreach($allProjectSprints as $targetSprint)
                            <option value="{{ $targetSprint->id }}">{{ $targetSprint->name }} ({{ ucfirst($targetSprint->status) }})</option>
                        @endforeach
                    </select>
                    <button wire:click="bulkMoveSelectedTasks({{ $sprint->id }})"
                            class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                        Move
                    </button>
                </div>
            </div>
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

    <template x-teleport="body">
        <div x-show="completeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/45" @click="completeModalOpen = false"></div>

            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-[0_20px_60px_rgba(0,0,0,0.18)]"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 @click.stop>

                <div class="flex items-center justify-between border-b border-hairline px-6 py-4">
                    <h3 class="text-[16px] font-semibold text-ink">Complete Sprint</h3>
                    <button type="button" @click="completeModalOpen = false" class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer">&times;</button>
                </div>

                <form method="POST" :action="completeUrlTemplate.replace('__SPRINT__', completeSprintId)">
                    @csrf
                    <input type="hidden" name="completion_action" :value="completeAction">

                    <div class="px-6 py-5 space-y-4">
                        <p class="text-[14px] font-medium text-ink">This sprint has <span class="text-[#b94040]" x-text="unfinishedCount"></span> unfinished tasks.</p>

                        <div class="space-y-3">
                            <button type="button" @click="completeAction = 'move_existing'" class="w-full text-left rounded-xl border px-4 py-3 transition-colors duration-150 cursor-pointer"
                                    :class="completeAction === 'move_existing' ? 'border-accent bg-[#fdf3ee]' : 'border-line bg-white hover:bg-canvas'">
                                <p class="text-[13px] font-semibold text-ink">Option A — Move to existing sprint</p>
                                <p class="text-[12px] text-muted mt-0.5">Move all unfinished tasks to another active or draft sprint, then complete this sprint.</p>
                            </button>

                            <div x-show="completeAction === 'move_existing'" x-cloak class="pl-2">
                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Target Sprint</label>
                                <select name="target_sprint_id" x-model="targetSprintId" class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                                    <option value="">Select sprint...</option>
                                    <template x-for="s in availableTargetSprints()" :key="s.id">
                                        <option :value="s.id" x-text="`${s.name} (${s.status})`"></option>
                                    </template>
                                </select>
                                <p x-show="availableTargetSprints().length === 0" x-cloak class="text-[12px] text-[#b94040] mt-1">No other active or draft sprint is available.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <button type="button" @click="completeAction = 'create_new'" class="w-full text-left rounded-xl border px-4 py-3 transition-colors duration-150 cursor-pointer"
                                    :class="completeAction === 'create_new' ? 'border-accent bg-[#fdf3ee]' : 'border-line bg-white hover:bg-canvas'">
                                <p class="text-[13px] font-semibold text-ink">Option B — Create new sprint</p>
                                <p class="text-[12px] text-muted mt-0.5">Create a new draft sprint and move all unfinished tasks there before completion.</p>
                            </button>

                            <div x-show="completeAction === 'create_new'" x-cloak class="pl-2">
                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">New Sprint Name</label>
                                <input type="text" name="new_sprint_name" x-model="newSprintName" class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150" placeholder="e.g. Sprint 2 — Remaining Work">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <button type="button" @click="completeAction = 'complete_anyway'" class="w-full text-left rounded-xl border px-4 py-3 transition-colors duration-150 cursor-pointer"
                                    :class="completeAction === 'complete_anyway' ? 'border-accent bg-[#fdf3ee]' : 'border-line bg-white hover:bg-canvas'">
                                <p class="text-[13px] font-semibold text-ink">Option C — Complete anyway</p>
                                <p class="text-[12px] text-muted mt-0.5">Keep unfinished tasks in this sprint and complete it as-is.</p>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-hairline bg-canvas px-6 py-3">
                        <button type="button" @click="completeModalOpen = false" class="px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                            Cancel
                        </button>

                        <button type="submit" :disabled="!canSubmitCompletion()" x-show="completeAction === 'move_existing'" x-cloak class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            Move tasks and complete
                        </button>
                        <button type="submit" :disabled="!canSubmitCompletion()" x-show="completeAction === 'create_new'" x-cloak class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            Create sprint, move tasks, and complete
                        </button>
                        <button type="submit" :disabled="!canSubmitCompletion()" x-show="completeAction === 'complete_anyway'" x-cloak class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            Complete anyway, keep tasks here.
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- Livewire loading indicator --}}
    <div wire:loading.delay class="fixed bottom-4 right-4 z-50 flex items-center gap-2 px-3 py-2 bg-white border border-line rounded-lg shadow-lg text-[12px] text-muted">
        <svg class="w-4 h-4 animate-spin text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Saving…
    </div>
</div>
