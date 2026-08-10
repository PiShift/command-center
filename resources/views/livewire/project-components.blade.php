<div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
    <div class="px-5 py-4 border-b border-hairline">
        <h2 class="text-[13px] font-semibold text-ink">Components</h2>
        <p class="text-[12px] text-muted mt-0.5">Allowed values for the task Component field on this project.</p>
    </div>

    <div class="px-5 py-4">
        @if(empty($components))
        <p class="text-[13px] text-muted">No components configured yet.</p>
        @else
        <ul class="flex flex-wrap gap-1.5 mb-3">
            @foreach($components as $index => $component)
            <li class="inline-flex items-center gap-1.5 text-[11px] font-medium rounded-full pl-2.5 pr-1.5 py-1 bg-hairline text-dim"
                wire:key="component-{{ $index }}">
                {{ $component }}
                @if(($usageCounts[$component] ?? 0) > 0)
                <span class="text-[10px] text-muted" title="{{ $usageCounts[$component] }} {{ Str::plural('task', $usageCounts[$component]) }} use this component">
                    {{ $usageCounts[$component] }}
                </span>
                @endif
                <button type="button"
                        wire:click="requestRemove({{ $index }})"
                        class="w-4 h-4 flex items-center justify-center rounded-full text-muted hover:text-[#b94040] cursor-pointer leading-none"
                        title="Remove component">×</button>
            </li>
            @endforeach
        </ul>
        @endif

        <form wire:submit="addComponent" class="flex items-center gap-2">
            <input type="text"
                   wire:model="newComponent"
                   placeholder="Add a component (e.g. Mobile)…"
                   class="flex-1 min-w-0 text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-1.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted placeholder:italic">
            <button type="submit"
                    class="px-3 py-1.5 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors cursor-pointer">
                Add
            </button>
        </form>
        @error('newComponent')
        <p class="text-[12px] text-[#b94040] mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Removal confirmation (when the component is in use) --}}
    @if($confirmingRemoval && $pendingRemoveIndex !== null && isset($components[$pendingRemoveIndex]))
    <template x-teleport="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45" wire:click="cancelRemove"></div>
            <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] overflow-hidden" @click.stop>
                <div class="px-6 py-4 border-b border-hairline">
                    <h3 class="text-[15px] font-semibold text-ink">Remove "{{ $components[$pendingRemoveIndex] }}"?</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-[13px] text-dim">
                        {{ $pendingRemoveUsage }} existing {{ Str::plural('task', $pendingRemoveUsage) }} still
                        {{ $pendingRemoveUsage === 1 ? 'has' : 'have' }} this component set. Removing it only affects the
                        dropdown options — those tasks keep their current value unchanged.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-hairline">
                    <button type="button" wire:click="cancelRemove"
                            class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">
                        Keep
                    </button>
                    <button type="button" wire:click="confirmRemove"
                            class="px-4 py-2 text-[13px] font-medium text-white bg-[#b94040] rounded-lg hover:bg-[#a33636] transition-colors cursor-pointer">
                        Remove
                    </button>
                </div>
            </div>
        </div>
    </template>
    @endif
</div>
