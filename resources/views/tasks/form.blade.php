<x-layouts.app :title="isset($task) ? 'Edit task' : 'New Task'">

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6 text-[13px]">
        <a href="{{ route('tasks.index') }}" class="text-muted hover:text-ink">Tasks</a>
        <span class="text-muted">/</span>
        <span class="text-ink">{{ isset($task) ? 'Edit' : 'New task' }}</span>
    </div>

    <div class="bg-white border border-line rounded-xl p-6">
        <h1 class="text-[15px] font-semibold text-ink mb-6">
            {{ isset($task) ? 'Edit task' : 'New Task' }}
        </h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($task) ? route('tasks.update', $task) : route('tasks.store') }}"
              x-data="{ source: '{{ old('source', $task->source ?? 'manual') }}' }"
              class="space-y-5">
            @csrf
            @if(isset($task)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                {{-- Title --}}
                <div class="col-span-2">
                    <label class="block text-[12px] font-medium text-dim mb-1">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $task->title ?? '') }}" required
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Project --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Project *</label>
                    <select name="project_id" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">— Select project —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ old('project_id', $task->project_id ?? request('project_id')) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Assignee --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Assignee</label>
                    <select name="assigned_to" class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">— None —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('assigned_to', $task->assigned_to ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Agent --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Agent (optional)</label>
                    <select name="agent_id" class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">— None —</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('agent_id', $task->agent_id ?? '') == $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }} — {{ $agent->runtime?->name ?? 'No runtime' }}
                            </option>
                        @endforeach
                    </select>
                    @if($agents->isEmpty())
                        <p class="mt-1 text-[12px] text-muted">No agents configured. Create one to enable AI execution.</p>
                    @endif
                </div>

                {{-- Type --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Type *</label>
                    <select name="type" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        @foreach(['bug' => 'Bug', 'feature' => 'Feature', 'change' => 'Change'] as $v => $l)
                            <option value="{{ $v }}" {{ old('type', $task->type ?? 'feature') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Component --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Component</label>
                    <input type="text" name="component" list="task-component-options"
                           value="{{ old('component', $task->component ?? '') }}"
                           placeholder="e.g. Mobile, Web, Backend"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                    <datalist id="task-component-options">
                        @foreach($componentOptions ?? [] as $componentOption)
                            <option value="{{ $componentOption }}"></option>
                        @endforeach
                    </datalist>
                </div>

                {{-- Priority --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Priority *</label>
                    <select name="priority" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $v => $l)
                            <option value="{{ $v }}" {{ old('priority', $task->priority ?? 'medium') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Status *</label>
                    <select name="status" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        @foreach($columns as $col)
                            <option value="{{ $col->slug }}" {{ old('status', $task->status ?? 'backlog') === $col->slug ? 'selected' : '' }}>{{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Due Date --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d') ?? '') }}"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Estimated Hours --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Estimated Hours</label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours', $task->estimated_hours ?? '') }}" min="0" step="0.5"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Description --}}
                <div class="col-span-2">
                    <label class="block text-[12px] font-medium text-dim mb-1">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">{{ old('description', $task->description ?? '') }}</textarea>
                </div>

                {{-- Labels --}}
                <div class="col-span-2">
                    <label class="block text-[12px] font-medium text-dim mb-1">Labels</label>
                    <input type="text" name="labels_csv"
                           value="{{ old('labels_csv', isset($task) ? implode(', ', $task->labels ?? []) : '') }}"
                           placeholder="Comma-separated, e.g. urgent, ios, refactor"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                {{-- Source --}}
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Source *</label>
                    <select name="source" x-model="source" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="manual">Manual</option>
                        <option value="ai-chat">AI Chat</option>
                    </select>
                </div>

                {{-- Original Input (ai-chat only) --}}
                <div class="col-span-2" x-show="source === 'ai-chat'" x-cloak>
                    <label class="block text-[12px] font-medium text-dim mb-1">Original Input</label>
                    <textarea name="original_input" rows="3"
                              class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">{{ old('original_input', $task->original_input ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-hairline">
                <a href="{{ route('tasks.index') }}" class="px-4 py-2 text-[13px] text-dim hover:text-ink">Cancel</a>
                <button type="submit"
                        class="px-4 py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-semibold rounded-lg transition-colors">
                    {{ isset($task) ? 'Save changes' : 'Create task' }}
                </button>
            </div>
        </form>
    </div>
</div>

</x-layouts.app>
