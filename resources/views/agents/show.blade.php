<x-layouts.app :title="$agent->name">

<style>
    dialog::backdrop {
        background-color: rgba(0, 0, 0, 0.45);
    }
</style>

<div class="max-w-6xl mx-auto space-y-6" x-data="{ tab: '{{ request('tab', 'activity') }}' }">

    @include('components.flash')

    <div class="flex items-center gap-2 text-[12px] text-muted">
        <a href="{{ route('agents.index') }}" class="hover:text-ink transition-colors">Agents</a>
        <span>›</span>
        <span class="text-ink font-medium">{{ $agent->name }}</span>
        @if($agent->archived_at)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-surface text-muted">Archived</span>
        @endif
    </div>

    <div class="flex gap-6 items-start">
        <div class="w-[280px] shrink-0 space-y-4">
            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <div class="mb-4" x-data>
                    <form action="{{ route('agents.update', $agent) }}" method="POST" enctype="multipart/form-data" class="relative w-14 h-14 group">
                        @csrf
                        @method('PUT')
                        <input type="file" name="avatar" accept="image/*" class="hidden" x-ref="agentAvatarInput" onchange="this.form.submit()">
                        <button type="button" @click="$refs.agentAvatarInput.click()" class="relative w-14 h-14 cursor-pointer rounded-xl overflow-hidden">
                            <x-agent-avatar :agent="$agent" size="14" />
                            <span class="absolute inset-0 bg-black/45 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center justify-center text-white">
                                @include('components.icon', ['name' => 'pencil'])
                            </span>
                        </button>
                    </form>
                </div>

                <h1 class="text-[17px] font-bold text-ink leading-tight">{{ $agent->name }}</h1>
                @if($agent->description)
                <p class="text-[12px] text-muted mt-1">{{ $agent->description }}</p>
                @endif

                <div class="flex items-center gap-1.5 mt-3">
                    <span class="w-2 h-2 rounded-full {{ $agent->runtime?->status === 'online' ? 'bg-[#2e7d55]' : 'bg-muted' }}"></span>
                    <span class="text-[12px] font-medium text-dim">{{ $agent->runtime?->status === 'online' ? 'Online' : 'Offline' }}</span>
                </div>
            </div>

            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Properties</p>
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-muted">Runtime</span>
                        <span class="text-[12px] text-dim font-medium truncate max-w-[150px]">{{ $agent->runtime?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-muted">Model</span>
                        <span class="text-[12px] text-dim truncate max-w-[150px]">{{ $agent->model ?: 'Default' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-muted">Visibility</span>
                        <span class="text-[12px] text-dim">{{ $agent->visibility === 'workspace' ? 'Workspace' : 'Personal' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-muted">Concurrency</span>
                        <span class="text-[12px] text-dim">{{ $agent->max_concurrent_tasks }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Details</p>
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-muted">Owner</span>
                        <span class="text-[12px] text-dim">{{ $agent->owner?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-muted">Created</span>
                        <span class="text-[12px] text-dim">{{ $agent->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-muted">Updated</span>
                        <span class="text-[12px] text-dim">{{ $agent->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)] space-y-2">
                @if($agent->archived_at)
                <form action="{{ route('agents.restore', $agent) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 text-[13px] font-medium text-white rounded-lg bg-[#2e7d55] hover:opacity-90 transition-colors cursor-pointer">Restore Agent</button>
                </form>
                @else
                <form action="{{ route('agents.archive', $agent) }}" method="POST" onsubmit="return confirm('Archive this agent?')">
                    @csrf
                    <button type="submit" class="w-full py-2 text-[13px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">Archive Agent</button>
                </form>
                @endif
                <form action="{{ route('agents.destroy', $agent) }}" method="POST" onsubmit="return confirm('Delete this agent permanently?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 text-[13px] font-medium text-[#b94040] bg-[#fff8f8] border border-[#ffd0d0] rounded-lg hover:bg-[#ffe5e5] transition-colors cursor-pointer">Delete Agent</button>
                </form>
            </div>
        </div>

        <div class="flex-1 min-w-0">
            <div class="inline-flex gap-0.5 rounded-xl p-1 bg-white border border-line shadow-[0_1px_3px_rgba(20,20,19,0.04)] mb-4">
                @foreach(['activity' => 'Activity', 'tasks' => 'Tasks', 'instructions' => 'Instructions', 'skills' => 'Skills'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]' : 'text-muted hover:text-dim'" class="px-4 py-1.5 text-[13px] font-medium rounded-lg transition-colors cursor-pointer">{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="tab === 'activity'" class="space-y-4">
                <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Now</p>
                    @if($activeTask)
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-accent animate-pulse shrink-0"></span>
                        <div>
                            <p class="text-[13px] font-medium text-ink">{{ $activeTask->task?->title ?? 'Working...' }}</p>
                            <p class="text-[11px] text-muted mt-0.5">{{ ucfirst($activeTask->status) }} · started {{ $activeTask->started_at?->diffForHumans() ?? 'just now' }}</p>
                        </div>
                    </div>
                    @else
                    <p class="text-[12px] text-muted">No active work</p>
                    <p class="text-[11px] text-muted mt-0.5 italic">This agent isn't running anything right now.</p>
                    @endif
                </div>

                <div class="bg-white border border-line rounded-xl p-5 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Last 30 Days</p>
                        <span class="text-[11px] text-muted">Performance</span>
                    </div>
                    @php
                        $total = (int) ($stats?->total_runs ?? 0);
                        $completed = (int) ($stats?->completed ?? 0);
                        $failed = (int) ($stats?->failed ?? 0);
                        $successPct = $total > 0 ? round(($completed / $total) * 100) : 0;
                        $avgSeconds = (int) ($stats?->avg_seconds ?? 0);
                        $avgMin = $avgSeconds > 0 ? intdiv($avgSeconds, 60) : 0;
                        $avgSec = $avgSeconds > 0 ? $avgSeconds % 60 : 0;
                    @endphp
                    <p class="text-[32px] font-bold text-ink leading-none">{{ $total }} <span class="text-[16px] font-normal text-muted">runs</span></p>
                    <div class="flex items-center gap-4 mt-2 text-[12px] text-muted flex-wrap">
                        <span>{{ $successPct }}% success</span>
                        @if($total > 0)
                        <span>avg {{ $avgMin }}m {{ $avgSec }}s</span>
                        @endif
                        @if($failed > 0)
                        <span class="text-[#b94040] font-medium">{{ $failed }} failed</span>
                        @endif
                    </div>
                </div>

                @if($recentWork->isNotEmpty())
                <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-hairline flex items-center justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Recent Work</p>
                        <span class="text-[11px] text-muted">{{ $recentWork->count() }} of {{ $total }}</span>
                    </div>
                    <div class="divide-y divide-hairline">
                        @foreach($recentWork as $run)
                        @php
                            $duration = $run->started_at && $run->completed_at ? $run->started_at->diffInSeconds($run->completed_at) : null;
                            $durationMin = $duration ? intdiv($duration, 60) : 0;
                            $durationSec = $duration ? $duration % 60 : 0;
                        @endphp
                        <button type="button" class="w-full text-left px-5 py-3 flex items-center gap-3 hover:bg-canvas transition-colors cursor-pointer" onclick="window.dispatchEvent(new CustomEvent('open-task', { detail: { id: {{ $run->task_id }} } }))">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 {{ $run->status === 'completed' ? 'bg-[#edf7f2]' : 'bg-[#fff8f8]' }}">
                                @if($run->status === 'completed')
                                <svg class="w-2.5 h-2.5 text-[#2e7d55]" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                @else
                                <svg class="w-2.5 h-2.5 text-[#b94040]" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                @endif
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-medium text-ink truncate"><span class="text-muted mr-1">#{{ $run->task_id }}</span>{{ $run->task?->title ?? 'Unknown task' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[11px] text-muted">{{ $run->completed_at?->diffForHumans() ?? '—' }}</p>
                                @if($duration)
                                <p class="text-[11px] text-muted">{{ $durationMin }}m {{ $durationSec }}s</p>
                                @endif
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <div x-show="tab === 'tasks'" class="space-y-3">
                @php
                    $statusOrder = [
                        'open' => 'Open',
                        'todo' => 'Todo',
                        'in-progress' => 'In Progress',
                        'in-review' => 'In Review',
                        'done' => 'Done',
                    ];
                @endphp
                @foreach($statusOrder as $slug => $label)
                @if(isset($tasks[$slug]) && $tasks[$slug]->isNotEmpty())
                <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
                    <div class="px-5 py-3 border-b border-hairline flex items-center gap-2">
                        <span class="text-[12px] font-semibold text-ink">{{ $label }}</span>
                        <span class="text-[11px] text-muted bg-surface border border-hairline rounded-full px-2 py-0.5">{{ $tasks[$slug]->count() }}</span>
                    </div>
                    <div class="divide-y divide-hairline">
                        @foreach($tasks[$slug] as $task)
                        <button type="button" class="w-full text-left px-5 py-3 flex items-center gap-3 hover:bg-canvas transition-colors cursor-pointer" onclick="window.dispatchEvent(new CustomEvent('open-task', { detail: { id: {{ $task->id }} } }))">
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-medium text-ink truncate">{{ $task->title }}</p>
                                @if($task->project)
                                <p class="text-[11px] text-muted mt-0.5 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: {{ $task->project->color ?? '#D97757' }}"></span>
                                    {{ $task->project->name }}
                                </p>
                                @endif
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach

                @if($tasks->isEmpty())
                <div class="bg-white border border-line rounded-xl p-10 text-center shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <p class="text-[13px] text-muted">No tasks assigned to this agent.</p>
                </div>
                @endif
            </div>

            <div x-show="tab === 'instructions'" class="space-y-4">
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <form action="{{ route('agents.update', $agent) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="px-5 py-4 border-b border-hairline">
                            <p class="text-[12px] text-muted">Define this agent's identity and working style. Markdown is supported in instructions.</p>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Name</label>
                                    <input name="name" value="{{ $agent->name }}" type="text" required class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Visibility</label>
                                    <select name="visibility" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent transition-colors appearance-none cursor-pointer">
                                        <option value="workspace" @selected($agent->visibility === 'workspace')>Workspace</option>
                                        <option value="private" @selected($agent->visibility === 'private')>Personal</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Model</label>
                                    <input name="model" value="{{ $agent->model }}" type="text" placeholder="gpt-4.1" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Description</label>
                                    <input name="description" value="{{ $agent->description }}" type="text" placeholder="What does this agent do?" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Max Concurrent Tasks</label>
                                    <input name="max_concurrent_tasks" value="{{ $agent->max_concurrent_tasks }}" type="number" min="1" max="50" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Instructions</label>
                                <textarea name="instructions" rows="16" placeholder="Define this agent's role, expertise, and working style." class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted font-mono">{{ $agent->instructions }}</textarea>
                            </div>
                        </div>
                        <div class="px-5 pb-5 flex justify-end">
                            <button type="submit" class="px-6 py-2 text-[13px] font-medium text-white rounded-lg bg-accent hover:bg-accent-hover transition-colors cursor-pointer">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="tab === 'skills'" class="space-y-4">
                <div class="bg-white border border-line rounded-xl overflow-hidden shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                    <div class="px-5 py-4 border-b border-hairline">
                        <p class="text-[12px] text-muted">Workspace skills assigned to this agent. Local runtime skills are always available automatically.</p>
                    </div>
                    <div class="p-5 space-y-4">
                        @if($assignedSkills->isNotEmpty())
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-3">Assigned Skills</p>
                            <div class="space-y-2">
                                @foreach($assignedSkills as $skill)
                                <div class="flex items-center justify-between gap-3 p-3 bg-surface border border-hairline rounded-lg">
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('skills.show', $skill) }}" class="text-[13px] font-medium text-ink hover:text-accent transition-colors truncate block">{{ $skill->name }}</a>
                                        @if($skill->description)
                                        <p class="text-[11px] text-muted truncate max-w-[300px]">{{ $skill->description }}</p>
                                        @endif
                                    </div>
                                    <form action="{{ route('agent-skills.detach', [$agent, $skill]) }}" method="POST" class="shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-muted hover:text-[#b94040] transition-colors cursor-pointer" title="Remove skill">
                                            @include('components.icon', ['name' => 'trash-2', 'size' => 16])
                                        </button>
                                    </form>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @else
                        <div class="bg-canvas border border-hairline rounded-xl p-6 text-center">
                            <p class="text-[13px] text-muted">No skills assigned yet.</p>
                        </div>
                        @endif

                        @if($availableSkills->isNotEmpty())
                        <div class="border-t border-hairline pt-4">
                            <button type="button"
                                    @click="$refs.skillsModal?.showModal?.()"
                                    class="px-4 py-2 text-[13px] font-medium text-accent bg-accent-light border border-accent-light rounded-lg hover:bg-[#fde8e1] transition-colors cursor-pointer">
                                + Add skills
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Skills Modal --}}
            <dialog @keydown.escape="$refs.skillsModal?.close?.()" x-ref="skillsModal" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); border: none; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.18); padding: 0;" class="max-w-md w-[90vw]">
                <form action="{{ route('agent-skills.attach', $agent) }}" method="POST" enctype="application/x-www-form-urlencoded" class="p-6 space-y-4">
                    {{ csrf_field() }}
                    <div>
                        <h3 class="text-[16px] font-semibold text-ink">Add Skills</h3>
                        <p class="text-[12px] text-muted mt-1">Select skills to assign to this agent.</p>
                    </div>

                    <div class="max-h-[400px] overflow-y-auto space-y-2 border border-hairline rounded-lg p-3">
                        @foreach($availableSkills as $skill)
                        <label class="flex items-center gap-3 p-2 hover:bg-surface rounded-lg cursor-pointer">
                            <input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" class="w-4 h-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-medium text-ink">{{ $skill->name }}</p>
                                @if($skill->description)
                                <p class="text-[11px] text-muted truncate">{{ $skill->description }}</p>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>

                    <div class="flex gap-2 justify-end pt-2 border-t border-hairline">
                        <button type="button" @click="$refs.skillsModal?.close?.()" class="px-4 py-2 text-[13px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-6 py-2 text-[13px] font-medium text-white rounded-lg bg-accent hover:bg-accent-hover transition-colors cursor-pointer">Add Selected</button>
                    </div>
                </form>
            </dialog>
        </div>
    </div>
</div>

<livewire:task-modal />

</x-layouts.app>