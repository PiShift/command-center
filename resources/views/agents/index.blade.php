<x-layouts.app title="Agents">

<div class="max-w-6xl mx-auto space-y-6" x-data="{ createOpen: false }">

    @include('components.flash')

    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-[22px] font-bold text-ink leading-tight">Agents</h1>
            <p class="text-[13px] text-dim mt-0.5">AI teammates that pick up tasks, comment, and update status.</p>
        </div>

        <button type="button"
                @click="createOpen = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-[13px] font-medium text-white rounded-lg bg-accent hover:bg-accent-hover transition-colors cursor-pointer">
            @include('components.icon', ['name' => 'plus'])
            New agent
        </button>
    </div>

    <div class="inline-flex gap-0.5 rounded-xl p-1 bg-white border border-line shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
        @foreach(['mine' => 'Mine', 'all' => 'All', 'archived' => 'Archived'] as $key => $label)
        <a href="{{ route('agents.index', ['tab' => $key]) }}"
           class="px-4 py-1.5 text-[13px] font-medium rounded-lg transition-colors duration-150 {{ $tab === $key ? 'bg-white text-ink shadow-[0_1px_3px_rgba(20,20,19,0.08)]' : 'text-muted hover:text-dim' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="bg-white border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
        @if($agents->isEmpty())
        <div class="py-16 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-surface flex items-center justify-center text-muted mb-3">
                @include('components.icon', ['name' => 'play'])
            </div>
            <p class="text-[13px] font-medium text-dim">No agents yet.</p>
            <p class="text-[12px] text-muted mt-1">Create an agent to start automating tasks.</p>
        </div>
        @else
        <table class="w-full">
            <thead>
                <tr class="bg-canvas border-b border-line">
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-5 py-3">Agent</th>
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-4 py-3">Status</th>
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-4 py-3">Owner</th>
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-4 py-3">Runtime</th>
                    <th class="text-left text-[11px] font-bold uppercase tracking-wider text-muted px-4 py-3">Last active</th>
                    <th class="text-right text-[11px] font-bold uppercase tracking-wider text-muted px-5 py-3">Runs</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hairline">
                @foreach($agents as $agent)
                @php
                    $runCount = $runCounts[$agent->id] ?? null;
                    $statusMap = [
                        'idle' => ['Idle', 'bg-surface text-muted'],
                        'working' => ['Working', 'bg-accent-light text-accent'],
                        'blocked' => ['Blocked', 'bg-[#fff8f8] text-[#b94040]'],
                        'error' => ['Error', 'bg-[#fff8f8] text-[#b94040]'],
                        'offline' => ['Offline', 'bg-surface text-muted'],
                    ];
                    [$statusLabel, $statusClass] = $statusMap[$agent->status] ?? [ucfirst($agent->status), 'bg-surface text-muted'];
                @endphp
                <tr class="hover:bg-canvas transition-colors cursor-pointer"
                    onclick="window.location='{{ route('agents.show', $agent) }}'">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-accent text-white">
                                @include('components.icon', ['name' => 'play'])
                            </div>
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-ink truncate">{{ $agent->name }}</p>
                                @if($agent->description)
                                <p class="text-[11px] text-muted truncate max-w-[300px]">{{ $agent->description }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white shrink-0 bg-accent">
                                {{ strtoupper(substr($agent->owner?->name ?? '?', 0, 1)) }}
                            </div>
                            <span class="text-[12px] text-dim">{{ $agent->owner?->name ?? '—' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="text-[12px] text-dim truncate max-w-[220px] block">{{ $agent->runtime?->name ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="text-[12px] text-muted">{{ $runCount?->last_active ? \Illuminate\Support\Carbon::parse($runCount->last_active)->diffForHumans() : '—' }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <span class="text-[12px] font-medium text-ink">{{ $runCount?->total ?? 0 }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div x-cloak x-show="createOpen" class="fixed inset-0 z-[9999] flex items-center justify-center px-4" @keydown.escape.window="createOpen = false">
        <div class="absolute inset-0 bg-black/45" @click="createOpen = false"></div>
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] max-h-[90vh] overflow-y-auto">
            <form action="{{ route('agents.store') }}" method="POST">
                @csrf
                <div class="px-6 pt-6 pb-4 border-b border-hairline flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-[16px] font-semibold text-ink">Create Agent</h2>
                        <p class="text-[12px] text-muted mt-0.5">Create a new AI agent for your workspace.</p>
                    </div>
                    <button type="button" @click="createOpen = false" class="w-8 h-8 rounded-full flex items-center justify-center text-muted hover:text-ink bg-surface hover:bg-hairline transition-colors cursor-pointer">
                        @include('components.icon', ['name' => 'x'])
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Name</label>
                        <input name="name" type="text" required placeholder="e.g. Laravel Dev Agent" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Description <span class="font-normal">(optional)</span></label>
                        <input name="description" type="text" placeholder="What does this agent do?" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Runtime</label>
                        <select name="runtime_id" required class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent transition-colors appearance-none cursor-pointer">
                            <option value="">Select a runtime...</option>
                            @foreach($runtimes as $runtime)
                            <option value="{{ $runtime->id }}">{{ $runtime->name }} — {{ $runtime->device_info }}</option>
                            @endforeach
                        </select>
                        @if($runtimes->isEmpty())
                        <p class="text-[11px] text-muted mt-1">No online runtimes. Start the daemon first.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Visibility</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-line cursor-pointer transition-colors has-[:checked]:border-accent has-[:checked]:bg-accent-light">
                                <input type="radio" name="visibility" value="workspace" checked class="mt-0.5 accent-accent">
                                <div>
                                    <p class="text-[13px] font-medium text-ink">Workspace</p>
                                    <p class="text-[11px] text-muted">All members can assign</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-line cursor-pointer transition-colors has-[:checked]:border-accent has-[:checked]:bg-accent-light">
                                <input type="radio" name="visibility" value="private" class="mt-0.5 accent-accent">
                                <div>
                                    <p class="text-[13px] font-medium text-ink">Personal</p>
                                    <p class="text-[11px] text-muted">Only you and admins can assign</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Max Concurrent Tasks</label>
                            <input name="max_concurrent_tasks" type="number" min="1" max="50" value="6" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Model <span class="font-normal">(optional)</span></label>
                            <input name="model" type="text" placeholder="gpt-4.1" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Instructions <span class="font-normal">(optional)</span></label>
                        <textarea name="instructions" rows="4" placeholder="Write what this agent should do, what to focus on, what to avoid..." class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Skills <span class="font-normal">(optional)</span></label>
                        <textarea name="skills" rows="3" placeholder="One skill per line or comma-separated" class="w-full text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted"></textarea>
                    </div>
                </div>

                <div class="px-6 pb-6 flex items-center justify-end gap-2">
                    <button type="button" @click="createOpen = false" class="px-4 py-2 text-[13px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline transition-colors cursor-pointer">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-[13px] font-medium text-white rounded-lg bg-accent hover:bg-accent-hover transition-colors cursor-pointer">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layouts.app>