<x-layouts.settings :title="$skill->name">

<div class="space-y-6" x-data="{ editing: false }">

    @include('components.flash')

    <div class="flex items-center gap-2 text-[12px] text-muted">
        <a href="{{ route('skills.index') }}" class="hover:text-ink transition-colors">Skills</a>
        <span>›</span>
        <span class="text-ink font-medium">{{ $skill->name }}</span>
    </div>

    <div class="flex gap-6">
        {{-- Left panel: File tree --}}
        <div class="w-[240px] shrink-0">
            <div class="bg-white border border-line rounded-xl p-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)] space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Files · 1</p>
                </div>
                <div class="border-t border-hairline pt-3">
                    <div class="px-2 py-1.5 rounded text-[12px] text-ink font-medium bg-surface">SKILL.md</div>
                </div>
                <button type="button"
                        disabled
                        class="w-full mt-4 px-3 py-2 text-[12px] font-medium text-muted bg-surface border border-hairline rounded-lg opacity-50 cursor-not-allowed">
                    + Add file
                </button>
            </div>
        </div>

        {{-- Middle panel: Content --}}
        <div class="flex-1 min-w-0">
            <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
                <form action="{{ route('skills.update', $skill) }}" method="POST" class="flex flex-col h-full">
                    @csrf
                    @method('PUT')

                    <div class="px-6 py-4 border-b border-hairline space-y-3">
                        <input
                            type="text"
                            name="name"
                            value="{{ $skill->name }}"
                            x-show="editing"
                            class="w-full text-[18px] font-semibold text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent"
                        >
                        <h2 x-show="!editing" class="text-[18px] font-semibold text-ink" @click="editing = true; $nextTick(() => $refs.descInput?.focus())">{{ $skill->name }}</h2>

                        <textarea
                            x-show="editing"
                            name="description"
                            rows="2"
                            placeholder="Describe when to use this skill..."
                            class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent resize-none"
                        >{{ $skill->description }}</textarea>
                        <p x-show="!editing" class="text-[13px] text-muted">{{ $skill->description ?: '(No description)' }}</p>

                        <div class="flex items-center justify-between text-[11px] text-muted pt-2">
                            <span>Workspace · Updated {{ $skill->updated_at->diffForHumans() }} by {{ $skill->creator?->name ?? 'Unknown' }}</span>
                        </div>
                    </div>

                    <div class="px-6 py-4 flex-1 flex flex-col">
                        <textarea
                            name="content"
                            rows="20"
                            placeholder="# Skill Template

Write your SKILL.md content here. Markdown is fully supported."
                            class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent resize-none font-mono flex-1"
                        >{{ $skill->content }}</textarea>
                    </div>

                    <div class="px-6 pb-6 flex items-center justify-end gap-2 border-t border-hairline pt-4 bg-canvas">
                        <button type="button" @click="editing = false" x-show="editing" class="px-4 py-2 text-[13px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" x-show="editing" class="px-6 py-2 text-[13px] font-medium text-white rounded-lg bg-accent hover:bg-accent-hover transition-colors cursor-pointer">Save Changes</button>
                        <button type="button" @click="editing = true" x-show="!editing" class="px-4 py-2 text-[13px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">Edit</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Right panel: Metadata --}}
        <div class="w-[240px] shrink-0 space-y-4">
            <div class="bg-white border border-line rounded-xl p-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)] space-y-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Metadata</p>
                <div class="border-t border-hairline pt-3 space-y-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Created</p>
                        <p class="text-[12px] text-dim mt-1">{{ $skill->created_at->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Updated</p>
                        <p class="text-[12px] text-dim mt-1">{{ $skill->updated_at->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Created By</p>
                        <p class="text-[12px] text-dim mt-1">{{ $skill->creator?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Files</p>
                        <p class="text-[12px] text-dim mt-1">1</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted">ID</p>
                        <p class="text-[11px] text-muted mt-1 font-mono truncate" title="{{ $skill->id }}">{{ substr($skill->id, 0, 8) }}...</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-line rounded-xl p-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)] space-y-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Used By {{ $skill->agents->count() }} Agent{{ $skill->agents->count() !== 1 ? 's' : '' }}</p>
                <div class="border-t border-hairline pt-3">
                    @if($skill->agents->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($skill->agents as $agent)
                        <div class="text-[12px] text-dim flex items-center justify-between gap-2">
                            <span>{{ $agent->name }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-[12px] text-muted italic">Not assigned to any agent yet.</p>
                    @endif
                </div>
            </div>

            <div class="space-y-2 bg-white border border-line rounded-xl p-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <form action="{{ route('skills.destroy', $skill) }}" method="POST" onsubmit="return confirm('Delete this skill? It will be removed from all agents.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 text-[13px] font-medium text-[#b94040] bg-[#fff8f8] border border-[#ffd0d0] rounded-lg hover:bg-[#ffe5e5] transition-colors cursor-pointer">Delete Skill</button>
                </form>
            </div>
        </div>
    </div>
</div>

</x-layouts.settings>
