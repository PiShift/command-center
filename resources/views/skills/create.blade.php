<x-layouts.settings title="Create Skill">

<div class="space-y-6">

    @include('components.flash')

    <div class="mb-6">
        <a href="{{ route('skills.index') }}" class="text-[12px] text-muted hover:text-ink transition-colors">
            ← Back to skills
        </a>
    </div>

    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] p-6 space-y-6">
        <div>
            <h1 class="text-[24px] font-semibold text-ink leading-tight">Create Skill</h1>
            <p class="text-[13px] text-muted mt-2">Define an instruction that agents can use when completing tasks.</p>
        </div>

        <form action="{{ route('skills.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Skill Name</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="e.g. code-review, bug-triage"
                    class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted"
                >
                @error('name')
                <p class="text-[11px] text-[#b94040] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Description</label>
                <p class="text-[12px] text-muted mb-2">One sentence on when to assign this skill to an agent.</p>
                <input
                    type="text"
                    name="description"
                    value="{{ old('description') }}"
                    placeholder="When this skill should be used..."
                    class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted"
                >
                @error('description')
                <p class="text-[11px] text-[#b94040] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 justify-end pt-4 border-t border-hairline">
                <a href="{{ route('skills.index') }}"
                   class="px-4 py-2 text-[13px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 text-[13px] font-medium text-white rounded-lg bg-accent hover:bg-accent-hover transition-colors cursor-pointer">
                    Create Skill
                </button>
            </div>
        </form>
    </div>
</div>

</x-layouts.settings>
