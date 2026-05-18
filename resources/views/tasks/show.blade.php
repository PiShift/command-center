<x-layouts.app :title="$task->title">

<div class="max-w-3xl mx-auto space-y-5">
    <div class="flex items-center gap-3 text-[13px] mb-2">
        <a href="{{ route('tasks.index') }}" class="text-muted hover:text-ink">Tasks</a>
        <span class="text-muted">/</span>
        <span class="text-ink truncate">{{ $task->title }}</span>
    </div>

    @include('components.flash')

    <div class="bg-white border border-line rounded-xl p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-[18px] font-bold text-ink mb-2">{{ $task->title }}</h1>
                <div class="flex items-center flex-wrap gap-2">
                    @include('components.badge', ['type' => 'status',   'value' => $task->status])
                    @include('components.badge', ['type' => 'priority', 'value' => $task->priority])
                    @include('components.badge', ['type' => 'type',     'value' => $task->type])
                    @include('components.badge', ['type' => 'source',   'value' => $task->source])
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <form method="POST" action="{{ route('tasks.advance', $task) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium border border-line rounded-lg text-dim hover:bg-hairline">
                        {{ match($task->status) { 'backlog' => 'Start', 'in-progress' => 'Mark Done', default => 'Re-open' } }}
                    </button>
                </form>
                @if(auth()->user()->hasPermission('tasks.edit_any'))
                <a href="{{ route('tasks.edit', $task) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-[12px] border border-line rounded-lg text-dim hover:bg-hairline">
                    @include('components.icon', ['name' => 'pencil'])
                    Edit
                </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-x-8 gap-y-3 mt-6 text-[13px] border-t border-hairline pt-5">
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Project</p>
                @if($task->project)
                    <a href="{{ route('projects.show', $task->project) }}" class="text-accent hover:underline">{{ $task->project->name }}</a>
                @else
                    <span class="text-dim">—</span>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Assignee</p>
                @if($task->assignee)
                    <p class="text-ink">{{ $task->assignee->name }}</p>
                @elseif(\Illuminate\Support\Facades\Gate::allows('claim', $task))
                    <form method="POST" action="{{ route('tasks.claim', $task) }}" class="mt-1">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-semibold rounded-lg border cursor-pointer transition-colors duration-150"
                                style="color:#2e7d55;background:#edf7f2;border-color:#b7e0ca"
                                onmouseover="this.style.background='#d6f0e4'"
                                onmouseout="this.style.background='#edf7f2'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59"/>
                            </svg>
                            Take this task
                        </button>
                    </form>
                @else
                    <p class="text-ink">—</p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Due Date</p>
                <p class="{{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-ink' }}">
                    {{ $task->due_date?->format('M d, Y') ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Estimated</p>
                <p class="text-ink">{{ $task->estimated_hours ? $task->estimated_hours . 'h' : '—' }}</p>
            </div>
            @if($task->labels)
            <div class="col-span-2">
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-1">Labels</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($task->labels as $label)
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-hairline text-dim">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        @if($task->description)
        <div class="mt-5 border-t border-hairline pt-4">
            <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-2">Description</p>
            <p class="text-[13px] text-ink whitespace-pre-line">{{ $task->description }}</p>
        </div>
        @endif

        {{-- ── Implementation Guide ──────────────────────────────────────────── --}}
        @php $canEditTask = auth()->user()->hasPermission('tasks.edit_any'); @endphp
        @if($task->guide || $canEditTask)
        <div class="mt-5 border-t border-hairline pt-4"
             x-data="{ editing: false, guide: {{ Js::from($task->guide ?? '') }} }">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                    <p class="text-[11px] font-medium text-muted uppercase tracking-wider">Implementation Guide</p>
                </div>
                @if($canEditTask)
                <button x-show="!editing" @click="editing = true"
                        class="text-[12px] font-medium text-muted hover:text-ink transition-colors cursor-pointer">
                    {{ $task->guide ? 'Edit' : 'Add guide' }}
                </button>
                @endif
            </div>

            {{-- Preview --}}
            <div x-show="!editing">
                @if($task->guide)
                <div class="guide-content">{!! Str::markdown($task->guide, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                @else
                <p class="text-[13px] text-muted italic">No guide yet.</p>
                @endif
            </div>

            {{-- Inline edit --}}
            @if($canEditTask)
            <div x-show="editing" x-cloak>
                <form method="POST" action="{{ route('tasks.update', $task) }}">
                    @csrf @method('PATCH')
                    {{-- Pass all required fields so validation passes --}}
                    <input type="hidden" name="title" value="{{ $task->title }}">
                    <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                    <input type="hidden" name="type" value="{{ $task->type }}">
                    <input type="hidden" name="priority" value="{{ $task->priority }}">
                    <input type="hidden" name="status" value="{{ $task->status }}">
                    <input type="hidden" name="source" value="{{ $task->source }}">
                    <textarea name="guide"
                              x-model="guide"
                              rows="14"
                              placeholder="Write the implementation guide in Markdown…"
                              class="w-full text-[13px] font-mono text-ink bg-surface border border-line rounded-lg px-4 py-3 outline-none focus:border-accent focus:bg-white transition-colors resize-y placeholder:text-muted mb-3"></textarea>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="px-4 py-1.5 bg-accent hover:bg-accent-hover text-white text-[12px] font-medium rounded-lg transition-colors cursor-pointer">
                            Save
                        </button>
                        <button type="button" @click="editing = false; guide = {{ Js::from($task->guide ?? '') }}"
                                class="px-4 py-1.5 border border-line rounded-lg text-[12px] text-dim hover:bg-surface transition-colors cursor-pointer">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Checklist ─────────────────────────────────────────────────────── --}}
        @php
            $checklists = $task->checklists;
            $total      = $checklists->count();
            $checked    = $checklists->where('is_checked', true)->count();
            $pct        = $total > 0 ? (int) round($checked / $total * 100) : 0;
        @endphp
        <div class="mt-5 border-t border-hairline pt-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider">Checklist</p>
                @if($total > 0)
                <span class="text-[11px] text-muted">{{ $checked }} / {{ $total }}</span>
                @endif
            </div>

            @if($total > 0)
            <div class="w-full h-1 bg-hairline rounded-full mb-3 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: #3d9970;"></div>
            </div>
            <ul class="space-y-0.5 mb-4">
                @foreach($checklists as $checkItem)
                <li class="group flex items-center gap-2.5 py-1 rounded-lg hover:bg-canvas px-1.5 -mx-1.5 transition-colors duration-100">
                    <form method="POST" action="{{ route('checklists.update', [$task, $checkItem]) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="is_checked" value="{{ $checkItem->is_checked ? '0' : '1' }}">
                        <button type="submit"
                                class="w-4 h-4 rounded flex-shrink-0 cursor-pointer accent-[#3d9970] flex items-center justify-center border transition-colors {{ $checkItem->is_checked ? 'bg-[#3d9970] border-[#3d9970]' : 'border-line hover:border-muted' }}">
                            @if($checkItem->is_checked)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            @endif
                        </button>
                    </form>
                    <span class="flex-1 text-[13px] leading-snug {{ $checkItem->is_checked ? 'line-through text-muted' : 'text-ink' }} truncate">
                        {{ $checkItem->label }}
                    </span>
                    <form method="POST" action="{{ route('checklists.destroy', [$task, $checkItem]) }}"
                          class="opacity-0 group-hover:opacity-100 transition-opacity">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-5 h-5 flex items-center justify-center rounded text-muted hover:text-[#b94040] cursor-pointer" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
            @endif

            {{-- Add item --}}
            <form method="POST" action="{{ route('checklists.store', $task) }}" class="flex items-center gap-2">
                @csrf
                <input type="text"
                       name="label"
                       placeholder="Add an item…"
                       autocomplete="off"
                       class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-1.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted placeholder:italic">
                <button type="submit"
                        class="px-3 py-1.5 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors cursor-pointer">
                    Add
                </button>
            </form>
        </div>

        @if($task->source === 'ai-chat' && $task->original_input)
        <div class="mt-5 border-t border-hairline pt-4">
            <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-2">Original AI Input</p>
            <p class="text-[13px] text-dim whitespace-pre-line bg-hairline rounded-lg p-3">{{ $task->original_input }}</p>
        </div>
        @endif
    </div>
</div>

</x-layouts.app>
