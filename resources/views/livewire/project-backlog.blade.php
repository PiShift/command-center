<div x-data="{
    aiPlanOpen: false,
    planStep: 1,
    planTab: 'paste',
    planNotes: '',
    planSelectedItems: [],
    planLoading: false,
    planError: null,
    planResult: null,
    planRawInput: '',

    togglePlanItem(id) {
        const idx = this.planSelectedItems.indexOf(id);
        if (idx === -1) this.planSelectedItems.push(id);
        else this.planSelectedItems.splice(idx, 1);
    },

    async analyzePlan() {
        this.planLoading = true;
        this.planError = null;
        this.planResult = null;

        let payload = {};
        if (this.planTab === 'paste') {
            if (!this.planNotes.trim()) {
                this.planError = 'Please enter some notes to analyze.';
                this.planLoading = false;
                return;
            }
            payload = { raw_notes: this.planNotes };
        } else {
            if (this.planSelectedItems.length === 0) {
                this.planError = 'Please select at least one backlog item.';
                this.planLoading = false;
                return;
            }
            payload = { item_ids: this.planSelectedItems };
        }

        this.planRawInput = JSON.stringify(payload);

        try {
            const res = await fetch('{{ route('ai.plan', $project) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (!res.ok) {
                this.planError = data.message || 'Something went wrong.';
            } else {
                this.planResult = data;
                this.planStep = 2;
            }
        } catch (e) {
            this.planError = 'Network error. Please try again.';
        } finally {
            this.planLoading = false;
        }
    },

    removeItem(sIdx, iIdx) {
        this.planResult.sprints[sIdx].items.splice(iIdx, 1);
    },
    removeSprint(sIdx) {
        this.planResult.sprints.splice(sIdx, 1);
    },
    addSprint() {
        if (!this.planResult) return;
        this.planResult.sprints.push({ name: 'New Sprint', rationale: '', items: [] });
    },
    moveItem(fromSprint, fromItem, toSprint) {
        const item = this.planResult.sprints[fromSprint].items.splice(fromItem, 1)[0];
        this.planResult.sprints[toSprint].items.push(item);
    },
}">
<div class="space-y-4">

    {{-- Flash inline --}}
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

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <h2 class="text-[16px] font-semibold text-ink">Backlog</h2>
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Bulk actions (shown when items selected) --}}
            @if(!empty($selectedItems))
            <div class="flex items-center gap-2 bg-white border border-line rounded-lg px-3 py-1.5 shadow-[0_1px_3px_rgba(20,20,19,0.06)]">
                <span class="text-[12px] font-medium text-ink">{{ count($selectedItems) }} selected</span>
                <span class="w-px h-4 bg-hairline"></span>
                {{-- Assign sprint --}}
                <select wire:change="bulkAssignSprint($event.target.value)"
                        class="text-[12px] text-muted bg-transparent border-0 focus:outline-none cursor-pointer">
                    <option value="" disabled selected>Assign sprint…</option>
                    <option value="0">No sprint</option>
                    @foreach($sprints->where('status', '!=', 'completed') as $sprint)
                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                    @endforeach
                </select>
                <span class="w-px h-4 bg-hairline"></span>
                {{-- Bulk promote --}}
                <button wire:click="bulkPromote" wire:confirm="Promote {{ count($selectedItems) }} item(s) to tasks?"
                        class="text-[12px] font-semibold text-[#2e7d55] hover:underline cursor-pointer">
                    Promote all
                </button>
                <span class="w-px h-4 bg-hairline"></span>
                {{-- Bulk delete --}}
                <button wire:click="bulkDelete" wire:confirm="Delete {{ count($selectedItems) }} selected item(s)? This cannot be undone."
                        class="text-[12px] font-semibold text-[#b94040] hover:underline cursor-pointer">
                    Delete
                </button>
                <span class="w-px h-4 bg-hairline"></span>
                <button wire:click="clearSelection"
                        class="text-[12px] text-muted hover:text-ink cursor-pointer">✕</button>
            </div>
            @endif

            {{-- Select all --}}
            @if($pendingItems->isNotEmpty() && empty($selectedItems))
            <button wire:click="selectAll"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-ink bg-white border border-line rounded-lg hover:bg-canvas transition-colors duration-150 cursor-pointer shadow-[0_1px_2px_rgba(20,20,19,0.04)]">
                Select all
            </button>
            @endif

            {{-- Add item --}}
            @if($canManage)
            <button wire:click="$set('showAddForm', true)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-ink bg-white border border-line rounded-lg hover:bg-canvas transition-colors duration-150 cursor-pointer shadow-[0_1px_2px_rgba(20,20,19,0.04)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Item
            </button>

            {{-- Plan with AI --}}
            <button @click="aiPlanOpen = true; planStep = 1; planTab = 'paste'; planNotes = ''; planError = null; planResult = null; planSelectedItems = []"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-semibold text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer shadow-[0_1px_2px_rgba(20,20,19,0.04)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM7.5 12a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM18.894 6.166a.75.75 0 0 0-1.06-1.06l-1.591 1.59a.75.75 0 1 0 1.06 1.061l1.591-1.59ZM21.75 12a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5H21a.75.75 0 0 1 .75.75ZM17.834 18.894a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 1 0-1.061 1.06l1.59 1.591ZM12 18a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-2.25A.75.75 0 0 1 12 18ZM7.758 17.303a.75.75 0 0 0-1.061-1.06l-1.591 1.59a.75.75 0 0 0 1.06 1.061l1.592-1.59ZM6 12a.75.75 0 0 1-.75.75H3a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 6 12ZM6.697 7.757a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 0 0-1.061 1.06l1.59 1.591Z"/>
                </svg>
                Plan with AI
            </button>
            @endif
        </div>
    </div>

    {{-- Add item inline form --}}
    @if($showAddForm && $canManage)
    <div class="bg-white border border-accent/30 rounded-xl shadow-[0_2px_8px_rgba(20,20,19,0.06)] overflow-hidden">
        <div class="px-5 py-4 border-b border-hairline bg-canvas">
            <p class="text-[13px] font-semibold text-ink">New Backlog Item</p>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Title <span class="text-[#b94040]">*</span></label>
                <input wire:model="addTitle" type="text" placeholder="Describe what needs to be built"
                       class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                @error('addTitle')<p class="text-[12px] text-[#b94040] mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Description</label>
                <textarea wire:model="addDescription" rows="2" placeholder="Optional details"
                          class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Guide / Notes</label>
                <textarea wire:model="addGuide" rows="2" placeholder="Technical notes, acceptance criteria"
                          class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none font-mono"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1.5">Sprint</label>
                <select wire:model="addSprintId"
                        class="w-full px-3 py-2 text-[13px] text-ink bg-surface border border-line rounded-lg focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 cursor-pointer">
                    <option value="">No sprint</option>
                    @foreach($sprints->where('status', '!=', 'completed') as $sprint)
                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-hairline bg-canvas">
            <button wire:click="$set('showAddForm', false)"
                    class="px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                Cancel
            </button>
            <button wire:click="createItem"
                    class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                Add to Backlog
            </button>
        </div>
    </div>
    @endif

    {{-- Backlog items list --}}
    <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        @if($pendingItems->isEmpty())
        <div class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-muted mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
            </svg>
            <p class="text-[14px] font-medium text-dim">Backlog is empty</p>
            <p class="text-[13px] text-muted mt-1">Add items or use AI to plan your next sprints.</p>
            @if($canManage)
            <button wire:click="$set('showAddForm', true)"
                    class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                + Add Item
            </button>
            @endif
        </div>
        @else

        {{-- Grouped by sprint --}}
        @php
            $noSprintItems = $grouped->get(0, collect());
            $sprintGroups = $grouped->forget(0);
        @endphp

        {{-- Unassigned group --}}
        @if($noSprintItems->isNotEmpty())
        <div>
            <div class="flex items-center gap-2 px-5 py-2 bg-canvas border-b border-hairline">
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Unassigned</span>
                <span class="text-[11px] text-muted">({{ $noSprintItems->count() }})</span>
            </div>
            <ul class="divide-y divide-hairline">
                @foreach($noSprintItems as $item)
                @include('livewire._backlog_row', ['item' => $item, 'canManage' => $canManage, 'sprints' => $sprints, 'users' => $users])
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Sprint groups --}}
        @foreach($sprints as $sprint)
        @php $sprintItems = $sprintGroups->get($sprint->id, collect()); @endphp
        @if($sprintItems->isNotEmpty())
        <div class="{{ !$noSprintItems->isEmpty() || !$loop->first ? 'border-t border-hairline' : '' }}">
            <div class="flex items-center gap-2 px-5 py-2 bg-canvas border-b border-hairline">
                @php
                    $sdot = match($sprint->status) {
                        'active' => '#2e7d55',
                        'completed' => '#3a6fba',
                        default => '#8c8c8a',
                    };
                @endphp
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $sdot }}"></span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $sprint->name }}</span>
                <span class="text-[11px] text-muted">({{ $sprintItems->count() }})</span>
            </div>
            <ul class="divide-y divide-hairline">
                @foreach($sprintItems as $item)
                @include('livewire._backlog_row', ['item' => $item, 'canManage' => $canManage, 'sprints' => $sprints, 'users' => $users])
                @endforeach
            </ul>
        </div>
        @endif
        @endforeach

        @endif
    </div>

    {{-- Promoted items toggle --}}
    @if($promotedCount > 0)
    <div>
        <button wire:click="$toggle('showPromoted')"
                class="flex items-center gap-2 text-[12px] font-medium text-muted hover:text-ink transition-colors cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-200 {{ $showPromoted ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
            Promoted items ({{ $promotedCount }})
        </button>

        @if($showPromoted && $promotedItems->isNotEmpty())
        <div class="mt-2 bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
            <ul class="divide-y divide-hairline">
                @foreach($promotedItems as $item)
                <li class="flex items-center gap-3 px-5 py-3 hover:bg-canvas transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#2e7d55] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
                    </svg>
                    <span class="flex-1 text-[13px] font-medium text-ink truncate">{{ $item->title }}</span>
                    <span class="text-[11px] text-muted">{{ $item->promoted_at?->format('M d, Y') }}</span>
                    @if($item->promotedTask)
                    <a href="{{ route('tasks.show', $item->promotedTask) }}"
                       class="text-[11px] font-medium text-accent hover:underline flex-shrink-0">
                        → View Task
                    </a>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    {{-- Livewire loading indicator --}}
    <div wire:loading.delay class="fixed bottom-4 right-4 z-50 flex items-center gap-2 px-3 py-2 bg-white border border-line rounded-lg shadow-lg text-[12px] text-muted">
        <svg class="w-4 h-4 animate-spin text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Saving…
    </div>
</div>

{{-- ── AI Planning Panel (Alpine-only, full-screen overlay) ─────────────────── --}}
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

                <div class="flex gap-0 border-b border-hairline mb-5">
                    <button type="button"
                            @click="planTab = 'paste'"
                            :class="planTab === 'paste' ? 'border-b-2 border-accent text-accent font-semibold' : 'text-muted hover:text-dim'"
                            class="px-4 py-2 text-[13px] -mb-px transition-colors duration-150 cursor-pointer">
                        Paste Notes
                    </button>
                    @if($pendingItems->count() > 0)
                    <button type="button"
                            @click="planTab = 'backlog'"
                            :class="planTab === 'backlog' ? 'border-b-2 border-accent text-accent font-semibold' : 'text-muted hover:text-dim'"
                            class="px-4 py-2 text-[13px] -mb-px transition-colors duration-150 cursor-pointer">
                        From Backlog
                        <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-surface border border-hairline rounded-full text-muted">{{ $pendingItems->count() }}</span>
                    </button>
                    @endif
                </div>

                <div x-show="planTab === 'paste'">
                    <textarea x-model="planNotes"
                              placeholder="e.g. We need to build a customer notification system. Users should be able to subscribe to email and SMS alerts. Admins can trigger bulk notifications. We also need a preference center. Later, add push notifications for mobile..."
                              rows="16"
                              class="w-full px-4 py-3 text-[14px] text-ink bg-surface border border-line rounded-xl placeholder:text-muted placeholder:text-[13px] focus:bg-white focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none font-mono leading-relaxed"></textarea>
                    <p class="text-[12px] text-muted mt-2">Be as detailed or rough as you like. The AI will do the heavy lifting.</p>
                </div>

                @if($pendingItems->count() > 0)
                <div x-show="planTab === 'backlog'" class="space-y-2">
                    <p class="text-[12px] text-muted mb-3">Select backlog items to include in the AI plan:</p>
                    @foreach($pendingItems as $item)
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
                        <div class="flex items-center gap-3 px-5 py-3.5 bg-canvas border-b border-hairline">
                            <input type="text" x-model="sprint.name"
                                   class="flex-1 text-[14px] font-semibold text-ink bg-transparent border-0 focus:outline-none focus:ring-0 placeholder:text-muted"
                                   placeholder="Sprint name">
                            <button type="button" @click="removeSprint(sIdx)"
                                    class="w-6 h-6 flex items-center justify-center rounded text-muted hover:text-[#b94040] hover:bg-[#fff0f0] transition-colors duration-150 cursor-pointer flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div x-show="sprint.rationale" class="px-5 py-2 text-[12px] text-muted bg-canvas border-b border-hairline" x-text="sprint.rationale"></div>
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
