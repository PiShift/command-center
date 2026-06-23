<x-layouts.app title="Skills">

<div class="max-w-6xl mx-auto space-y-6">

    @include('components.flash')

    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-[24px] font-semibold text-ink leading-tight">Skills</h1>
            <p class="text-[13px] text-dim mt-1">Instructions any agent in this workspace can use.</p>
        </div>

        <button type="button"
                onclick="window.location='{{ route('skills.create') }}'"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-[13px] font-medium text-white rounded-lg bg-accent hover:bg-accent-hover transition-colors cursor-pointer">
            @include('components.icon', ['name' => 'plus'])
            New skill
        </button>
    </div>

    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        @if($skills->isEmpty())
        <div class="py-16 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-surface flex items-center justify-center text-muted mb-3">
                @include('components.icon', ['name' => 'book'])
            </div>
            <p class="text-[13px] font-medium text-dim">No skills yet.</p>
            <p class="text-[12px] text-muted mt-1">Create a skill to get started.</p>
        </div>
        @else
        <table class="w-full">
            <thead>
                <tr class="bg-canvas border-b border-hairline">
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-5 py-3">Skill Name</th>
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-4 py-3">Used By</th>
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-4 py-3">Added By</th>
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-4 py-3">Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hairline">
                @foreach($skills as $skill)
                <tr class="hover:bg-canvas transition-colors cursor-pointer"
                    onclick="window.location='{{ route('skills.show', $skill['id']) }}'">
                    <td class="px-5 py-3.5">
                        <p class="text-[13px] font-medium text-ink">{{ $skill['name'] }}</p>
                        @if($skill['description'])
                        <p class="text-[11px] text-muted truncate max-w-[400px] mt-0.5">{{ $skill['description'] }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="text-[12px] text-dim">{{ $skill['agent_count'] }} agent{{ $skill['agent_count'] !== 1 ? 's' : '' }}</span>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="text-[12px] text-dim">{{ $skill['created_by'] }}</span>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="text-[12px] text-muted">{{ $skill['updated_at'] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

</x-layouts.app>
