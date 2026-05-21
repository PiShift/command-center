<li class="hover:bg-canvas transition-colors duration-150"
    :class="$wire.selectedItems.includes({{ $item->id }}) ? 'bg-accent-light' : ''">

    {{-- Main row --}}
    <div class="flex items-start gap-3 px-5 py-3.5">
        @if($canManage)
        <div class="flex-shrink-0 mt-0.5">
            <input type="checkbox"
                   wire:click="toggleItem({{ $item->id }})"
                   :checked="$wire.selectedItems.includes({{ $item->id }})"
                   class="w-4 h-4 rounded border-line text-accent cursor-pointer accent-[#D97757]">
        </div>
        @endif

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[13.5px] font-semibold text-ink">{{ $item->title }}</span>
                @if($item->status === 'refined')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#eef3fb] text-[#3a6fba]">Refined</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-surface text-muted">Raw</span>
                @endif
                @if($item->guide)
                <span title="Has guide" class="text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </span>
                @endif
            </div>
            @if($item->description)
            <p class="text-[12px] text-muted mt-0.5 truncate">{{ Str::limit($item->description, 100) }}</p>
            @endif
        </div>

        @if($canManage)
        <div class="flex items-center gap-1 flex-shrink-0 mt-0.5">
            {{-- Edit --}}
            <button wire:click="editItem({{ $item->id }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer"
                    title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                </svg>
            </button>
            {{-- Delete --}}
            <button wire:click="deleteItem({{ $item->id }})"
                    wire:confirm="Delete this backlog item? This cannot be undone."
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-[#b94040] hover:bg-[#fff0f0] transition-colors duration-150 cursor-pointer"
                    title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
            </button>
            {{-- Promote --}}
            @if(!$item->promoted)
            <button wire:click="openPromote({{ $item->id }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-[#2e7d55] hover:bg-[#edf7f2] transition-colors duration-150 cursor-pointer"
                    title="Promote to task">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
                </svg>
            </button>
            @endif
        </div>
        @endif
    </div>

    {{-- Inline edit form --}}
    @if($editingItem === $item->id)
    <div class="px-5 pb-4 pt-1 bg-canvas border-t border-hairline space-y-3">
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Title</label>
            <input wire:model="editTitle" type="text"
                   class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
            @error('editTitle')<p class="text-[12px] text-[#b94040] mt-0.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Description</label>
            <textarea wire:model="editDescription" rows="2"
                      class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Guide / Notes</label>
            <textarea wire:model="editGuide" rows="2"
                      class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none font-mono"></textarea>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Sprint</label>
            <select wire:model="editSprintId"
                    class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 cursor-pointer">
                <option value="">No sprint</option>
                @foreach($sprints->where('status', '!=', 'completed') as $sprint)
                <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center justify-end gap-2">
            <button wire:click="cancelEdit"
                    class="px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                Cancel
            </button>
            <button wire:click="saveItem"
                    class="px-3 py-1.5 text-[12px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors duration-150 cursor-pointer">
                Save
            </button>
        </div>
    </div>
    @endif

    {{-- Inline promote form --}}
    @if($promotingItem === $item->id)
    <div class="px-5 pb-4 pt-1 bg-[#fafdf9] border-t border-[#b7e0ca] space-y-3">
        <p class="text-[12px] font-semibold text-[#2e7d55] pt-2">Promote to Task</p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Title</label>
                <input wire:model="promoteTitle" type="text"
                       class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
                @error('promoteTitle')<p class="text-[12px] text-[#b94040] mt-0.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Sprint</label>
                <select wire:model="promoteSprintId"
                        class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 cursor-pointer">
                    <option value="">No sprint</option>
                    @foreach($sprints->where('status', '!=', 'completed') as $sprint)
                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Description</label>
            <textarea wire:model="promoteDescription" rows="2"
                      class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 resize-none"></textarea>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Type</label>
                <select wire:model="promoteType"
                        class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 cursor-pointer">
                    <option value="feature">Feature</option>
                    <option value="bug">Bug</option>
                    <option value="change">Change</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Priority</label>
                <select wire:model="promotePriority"
                        class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 cursor-pointer">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Weight (1–5)</label>
                <input wire:model="promoteWeight" type="number" min="1" max="5"
                       class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 text-center">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Assign to</label>
                <select wire:model="promoteAssignedTo"
                        class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150 cursor-pointer">
                    <option value="">Unassigned</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted mb-1">Due date</label>
                <input wire:model="promoteDueDate" type="date"
                       class="w-full px-3 py-2 text-[13px] text-ink bg-white border border-line rounded-lg focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/15 transition-all duration-150">
            </div>
        </div>
        <div class="flex items-center justify-end gap-2">
            <button wire:click="cancelPromote"
                    class="px-3 py-1.5 text-[12px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors duration-150 cursor-pointer">
                Cancel
            </button>
            <button wire:click="promoteItem"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 text-[12px] font-medium text-white bg-[#2e7d55] hover:bg-[#256647] rounded-lg transition-colors duration-150 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
                </svg>
                Promote to Task
            </button>
        </div>
    </div>
    @endif
</li>
