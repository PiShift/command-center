<x-layouts.app :title="isset($project) ? 'Edit ' . $project->name : 'New Project'">

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.index') }}" class="text-muted hover:text-ink">Projects</a>
        <span class="text-muted">/</span>
        <span class="text-ink">{{ isset($project) ? 'Edit' : 'New project' }}</span>
    </div>

    <div class="bg-white border border-line rounded-xl p-6">
        <h1 class="text-[15px] font-semibold text-ink mb-6">
            {{ isset($project) ? 'Edit ' . $project->name : 'New Project' }}
        </h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($project) ? route('projects.update', $project) : route('projects.store') }}"
              class="space-y-5">
            @csrf
            @if(isset($project)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                {{-- Name --}}
                <div class="col-span-2">
                    <label class="block text-[12px] font-medium text-dim mb-1">Project Name *</label>
                    <input type="text" name="name" value="{{ old('name', $project->name ?? '') }}" required
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Customer</label>
                    <select name="customer_id" class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">— None —</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id', $project->customer_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Color --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Color</label>
                    <input type="color" name="color" value="{{ old('color', $project->color ?? '#D97757') }}"
                           class="w-full h-10 border border-line rounded-lg cursor-pointer">
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Status *</label>
                    <select name="status" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        @foreach(['active' => 'Active', 'paused' => 'Paused', 'complete' => 'Complete'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $project->status ?? 'active') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Health --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Health *</label>
                    <select name="health" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        @foreach(['on-track' => 'On Track', 'at-risk' => 'At Risk', 'blocked' => 'Blocked'] as $val => $label)
                            <option value="{{ $val }}" {{ old('health', $project->health ?? 'on-track') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- GitHub Repo --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">GitHub Repo</label>
                    <input type="text" name="github_repo" value="{{ old('github_repo', $project->github_repo ?? '') }}" placeholder="owner/repo"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Website --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Website / Domain</label>
                    <input type="url" name="website" value="{{ old('website', $project->website ?? '') }}" placeholder="https://example.com"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Stack --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Stack</label>
                    <input type="text" name="stack" value="{{ old('stack', $project->stack ?? '') }}" placeholder="Laravel, Vue, Tailwind"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Components --}}
                <div class="col-span-2"
                     x-data="{
                         items: {{ Js::from(array_values(old('components', $project->components ?? []) ?? [])) }},
                         draft: '',
                         add() {
                             const value = this.draft.trim();
                             if (!value) return;
                             if (!this.items.some(i => i.toLowerCase() === value.toLowerCase())) this.items.push(value);
                             this.draft = '';
                         },
                         remove(index) { this.items.splice(index, 1); }
                     }">
                    <label class="block text-[12px] font-medium text-dim mb-1">
                        Components
                        <x-tooltip text="Allowed Component/Platform values for tasks in this project (e.g. Mobile, Admin Panel, Backend). Shown as a dropdown on the task form and as a board filter." />
                    </label>
                    <div class="flex flex-wrap items-center gap-1.5 px-3 py-2 border border-line rounded-lg bg-white focus-within:ring-2 focus-within:ring-accent">
                        <template x-for="(item, index) in items" :key="index">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium rounded-full px-2 py-0.5 bg-hairline text-dim">
                                <span x-text="item"></span>
                                <button type="button" @click="remove(index)" class="text-muted hover:text-[#b94040] cursor-pointer leading-none">×</button>
                                <input type="hidden" name="components[]" :value="item">
                            </span>
                        </template>
                        <input type="text" x-model="draft"
                               @keydown.enter.prevent="add()"
                               @keydown.,.prevent="add()"
                               @blur="add()"
                               placeholder="Type and press Enter to add"
                               class="flex-1 min-w-[140px] text-[13px] text-ink bg-transparent outline-none placeholder:text-muted">
                    </div>
                </div>

                {{-- Slack Channel --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">
                        Slack Channel
                        <x-tooltip text="Notifications for this project will be sent to this channel. Leave empty to use the default workspace channel." />
                    </label>
                    <input type="text" name="slack_channel" value="{{ old('slack_channel', $project->slack_channel ?? '') }}" placeholder="#channel-name"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Start Date --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', ($project->start_date ?? null)?->format('Y-m-d') ?? '') }}"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Deadline --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Deadline</label>
                    <input type="date" name="deadline" value="{{ old('deadline', ($project->deadline ?? null)?->format('Y-m-d') ?? '') }}"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Description --}}
                <div class="col-span-2">
                    <label class="block text-[12px] font-medium text-dim mb-1">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">{{ old('description', $project->description ?? '') }}</textarea>
                </div>
            </div>

            {{-- Guide --}}
            <div x-data="{
                    filename: '',
                    loadFile(event) {
                        const file = event.target.files[0];
                        if (!file) return;
                        this.filename = file.name;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.$refs.guide.value = e.target.result;
                        };
                        reader.readAsText(file);
                    }
                }"
                 class="border border-line rounded-xl p-5 space-y-4">

                <div>
                    <p class="text-[13px] font-semibold text-ink">Project Guide</p>
                    <p class="text-[12px] text-muted mt-0.5">Describe the project structure, stack, file paths, architecture. This will be used as context for AI features.</p>
                </div>

                <textarea name="guide" rows="10" x-ref="guide"
                          class="w-full px-3 py-2 text-[13px] font-mono border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent resize-y">{{ old('guide', $project->guide ?? '') }}</textarea>

                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 px-3 py-1.5 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg cursor-pointer hover:bg-hairline hover:text-ink transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        Import .md file
                        <input type="file" accept=".md" class="sr-only" @change="loadFile($event)">
                    </label>
                    <span x-show="filename" x-text="filename" class="text-[12px] text-muted italic"></span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-hairline">
                <a href="{{ route('projects.index') }}" class="px-4 py-2 text-[13px] text-dim hover:text-ink">Cancel</a>
                <button type="submit"
                        class="px-4 py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-semibold rounded-lg transition-colors">
                    {{ isset($project) ? 'Save changes' : 'Create project' }}
                </button>
            </div>
        </form>
    </div>
</div>

</x-layouts.app>
