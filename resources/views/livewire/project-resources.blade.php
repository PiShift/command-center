<div class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-[15px] font-semibold text-ink">Project Resources</h3>
            <p class="text-[12px] text-muted mt-0.5">Link GitHub repos and local directories so agents know where to work.</p>
        </div>
        <button wire:click="$set('showForm', true)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-white rounded-lg transition-colors cursor-pointer"
                style="background:#D97757">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Resource
        </button>
    </div>

    {{-- Add form --}}
    @if($showForm)
    <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <h4 class="text-[13px] font-semibold text-ink mb-4">Add Resource</h4>

        {{-- Type toggle --}}
        <div class="flex gap-2 mb-4">
            <button wire:click="$set('resourceType', 'github_repo')"
                    class="px-3 py-1.5 text-[12px] font-medium rounded-lg border transition-colors cursor-pointer {{ $resourceType === 'github_repo' ? 'bg-ink text-white border-ink' : 'bg-surface text-muted border-line hover:text-ink' }}">
                GitHub Repo
            </button>
            <button wire:click="$set('resourceType', 'local_directory')"
                    class="px-3 py-1.5 text-[12px] font-medium rounded-lg border transition-colors cursor-pointer {{ $resourceType === 'local_directory' ? 'bg-ink text-white border-ink' : 'bg-surface text-muted border-line hover:text-ink' }}">
                Local Directory
            </button>
        </div>

        @if($resourceType === 'github_repo')
        {{-- GitHub repo form --}}
        <div class="space-y-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Repository URL</label>
                <input wire:model.blur="gitUrl"
                       type="url"
                       placeholder="https://github.com/org/repo"
                       class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
                @error('gitUrl') <p class="text-[11px] text-[#b94040] mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Label <span class="font-normal">(optional)</span></label>
                <input wire:model.blur="label"
                       type="text"
                       placeholder="e.g. Main repo"
                       class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
            </div>
        </div>

        @else
        {{-- Local directory form --}}
        <div class="space-y-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Runtime</label>
                <select wire:model="runtimeId"
                        class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent transition-colors appearance-none cursor-pointer">
                    <option value="">Select a runtime...</option>
                    @foreach($runtimes as $runtime)
                    <option value="{{ $runtime->id }}">{{ $runtime->name }} — {{ $runtime->device_info }}</option>
                    @endforeach
                </select>
                @error('runtimeId') <p class="text-[11px] text-[#b94040] mt-1">{{ $message }}</p> @enderror
                @if($runtimes->isEmpty())
                <p class="text-[11px] text-muted mt-1">No online runtimes. Start the daemon first.</p>
                @endif
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Local Path</label>
                <input wire:model.blur="localPath"
                       type="text"
                       placeholder="/Users/you/projects/myapp"
                       class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted font-mono">
                @error('localPath') <p class="text-[11px] text-[#b94040] mt-1">{{ $message }}</p> @enderror
                <p class="text-[11px] text-muted mt-1">Enter the absolute path on the machine running the daemon.</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Label <span class="font-normal">(optional)</span></label>
                <input wire:model.blur="label"
                       type="text"
                       placeholder="e.g. My MacBook"
                       class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
            </div>
        </div>
        @endif

        <div class="flex gap-2 mt-4">
            <button wire:click="addResource"
                    class="px-4 py-2 text-[12px] font-medium text-white rounded-lg transition-colors cursor-pointer"
                    style="background:#D97757">
                Save Resource
            </button>
            <button wire:click="$set('showForm', false)"
                    class="px-4 py-2 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">
                Cancel
            </button>
        </div>
    </div>
    @endif

    {{-- Resources list --}}
    @if($resources->isEmpty() && !$showForm)
    <div class="bg-white border border-line rounded-xl p-10 text-center shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        <p class="text-[13px] text-muted">No resources linked yet.</p>
        <p class="text-[12px] text-muted mt-1">Add a GitHub repo or local directory so agents know where to work.</p>
    </div>
    @else
    <div class="space-y-2">
        @foreach($resources as $resource)
        <div class="bg-white border border-line rounded-xl px-5 py-4 flex items-center gap-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)]"
             wire:key="resource-{{ $resource->id }}">

            {{-- Icon --}}
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                 style="background: {{ $resource->resource_type === 'github_repo' ? '#f0f0ff' : '#f0faf5' }}">
                @if($resource->resource_type === 'github_repo')
                <svg class="w-4 h-4" fill="currentColor" style="color:#7c3aed" viewBox="0 0 24 24">
                    <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                </svg>
                @else
                <svg class="w-4 h-4" fill="none" style="color:#2e7d55" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-medium text-ink truncate">
                    {{ $resource->label ?: ($resource->resource_type === 'github_repo' ? ($resource->resource_ref['url'] ?? '—') : ($resource->resource_ref['local_path'] ?? '—')) }}
                </p>
                <p class="text-[11px] text-muted font-mono truncate mt-0.5">
                    @if($resource->resource_type === 'github_repo')
                        {{ $resource->resource_ref['url'] ?? '—' }}
                    @else
                        {{ $resource->resource_ref['local_path'] ?? '—' }}
                        <span class="font-sans ml-2 text-hairline">daemon: {{ substr($resource->resource_ref['daemon_id'] ?? '—', 0, 8) }}...</span>
                    @endif
                </p>
            </div>

            {{-- Type badge --}}
            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md shrink-0"
                  style="{{ $resource->resource_type === 'github_repo' ? 'background:#f0f0ff;color:#7c3aed' : 'background:#f0faf5;color:#2e7d55' }}">
                {{ $resource->resource_type === 'github_repo' ? 'GitHub' : 'Local' }}
            </span>

            {{-- Delete --}}
            <button wire:click="deleteResource({{ $resource->id }})"
                    wire:confirm="Remove this resource?"
                    class="w-7 h-7 rounded-full flex items-center justify-center text-muted hover:text-[#b94040] transition-colors cursor-pointer shrink-0"
                    style="background: rgba(0,0,0,0.05)">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endforeach
    </div>
    @endif
</div>
