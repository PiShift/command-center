<x-layouts.settings :title="isset($template) && $template ? 'Edit ' . $template->name : 'New Checklist Template'">

<div>
    <div class="flex items-center gap-3 mb-6 text-[13px]">
        <a href="{{ route('settings.checklist-templates.index') }}" class="text-muted hover:text-ink">Checklist Templates</a>
        <span class="text-muted">/</span>
        <span class="text-ink">{{ isset($template) && $template ? 'Edit' : 'New template' }}</span>
    </div>

    <div class="bg-white border border-line rounded-xl p-6">
        <h1 class="text-[15px] font-semibold text-ink mb-6">
            {{ isset($template) && $template ? 'Edit ' . $template->name : 'New Checklist Template' }}
        </h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($template) && $template ? route('settings.checklist-templates.update', $template) : route('settings.checklist-templates.store') }}"
              class="space-y-5">
            @csrf
            @if(isset($template) && $template) @method('PUT') @endif

            {{-- Name --}}
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Template Name *</label>
                <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}" required
                       placeholder="e.g. Web Feature DoD"
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- Project rule --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Applies to Project</label>
                    <select name="project_id" class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">All projects</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ old('project_id', $template->project_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Type rule --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Applies to Task Type</label>
                    <select name="type" class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">All types</option>
                        @foreach(['bug' => 'Bug', 'feature' => 'Feature', 'change' => 'Change'] as $v => $l)
                            <option value="{{ $v }}" {{ old('type', $template->type ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Items --}}
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Checklist Items *</label>
                <textarea name="items" rows="8" required
                          placeholder="One item per line, e.g.&#10;Design reviewed&#10;Tests written&#10;Deployed to staging"
                          class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent resize-y">{{ old('items', isset($template) && $template ? $template->items->pluck('label')->join("\n") : '') }}</textarea>
                <p class="mt-1 text-[12px] text-muted">One item per line. Blank lines and duplicates are removed.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-hairline">
                <a href="{{ route('settings.checklist-templates.index') }}" class="px-4 py-2 text-[13px] text-dim hover:text-ink">Cancel</a>
                <button type="submit"
                        class="px-4 py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-semibold rounded-lg transition-colors">
                    {{ isset($template) && $template ? 'Save changes' : 'Create template' }}
                </button>
            </div>
        </form>
    </div>
</div>

</x-layouts.settings>
