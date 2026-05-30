{{-- AI Chat Panel — Livewire component --}}
<div
    x-data="{
        open: @entangle('isOpen').live,
        isStreaming: @entangle('isStreaming').live,
        inputText: @entangle('input').live,
        projectIdState: @entangle('projectId').live,
        streamingText: '',
        streamingError: '',
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
        async streamMessage(detail) {
            this.streamingText = '';
            this.streamingError = '';
            try {
                const csrfToken = document.querySelector('meta[name=csrf-token]');
                const response = await fetch('/ai/conversation/stream', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                        'Accept': 'text/event-stream',
                    },
                    body: JSON.stringify({
                        project_id:      detail.projectId,
                        message:         detail.message,
                        conversation_id: detail.conversationId,
                        context:         detail.context,
                    }),
                });
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                const reader  = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();
                    for (const line of lines) {
                        if (!line.startsWith('data: ')) continue;
                        const payload = line.slice(6).trim();
                        if (payload === '[DONE]') {
                            const textToSave = this.streamingText;
                            this.streamingText = '';
                            await $wire.saveAssistantMessage(textToSave);
                            this.$nextTick(() => this.scrollToBottom());
                            return;
                        }
                        try {
                            const parsed = JSON.parse(payload);
                            if (parsed.chunk) {
                                this.streamingText += parsed.chunk;
                                this.$nextTick(() => this.scrollToBottom());
                            }
                        } catch (_) {}
                    }
                }
                if (this.streamingText) {
                    const textToSave = this.streamingText;
                    this.streamingText = '';
                    await $wire.saveAssistantMessage(textToSave);
                }
            } catch (err) {
                console.error('AI stream error:', err);
                this.streamingError = 'Connection error. Please try again.';
                $wire.set('isStreaming', false);
            }
        }
    }"
    x-init="
        $watch('open', val => { if (val) scrollToBottom() });
        $wire.$watch('messages', () => scrollToBottom());
    "
    x-on:open-ai-chat.window="$wire.handleOpenEvent($event.detail?.projectId ?? null)"
    x-on:begin-stream.window="streamMessage($event.detail)"
>

    {{-- ── Floating trigger button ─────────────────────────────────────────── --}}
    <button
        wire:click="toggle"
        class="fixed bottom-6 right-6 z-50 w-11 h-11 rounded-full inline-flex items-center justify-center bg-accent hover:bg-accent-hover text-white shadow-[0_4px_14px_rgba(20,20,19,0.18)] transition-all duration-150 hover:scale-105 focus:outline-none cursor-pointer"
        title="AI Assistant (⌘K)"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/>
        </svg>
    </button>

    {{-- ── Mobile backdrop ─────────────────────────────────────────────────── --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="$wire.toggle()"
        class="fixed inset-0 bg-black/30 z-40 sm:hidden"
    ></div>

    {{-- ── Slide-in panel ───────────────────────────────────────────────────── --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition transform duration-250 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform duration-200 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex flex-col w-full sm:w-[560px] bg-white border-l border-line shadow-[0_0_60px_rgba(0,0,0,0.14)]"
    >

        {{-- ── Header — compact two-row ─────────────────────────────────────── --}}
        <div class="shrink-0 border-b border-hairline bg-white">

            {{-- Row 1: title + history icon + close --}}
            <div class="flex items-center justify-between px-4 pt-3 pb-2">
                <div class="flex items-center gap-1.5">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-accent shrink-0">
                        <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/>
                    </svg>
                    <span class="text-[14px] font-semibold text-ink">AI Assistant</span>
                </div>

                <div class="flex items-center gap-1.5">
                    {{-- History icon + dropdown --}}
                    @if($projectId && count($recentConversations) > 0)
                    <div x-data="{ histOpen: false }" class="relative">
                        <button
                            x-on:click="histOpen = !histOpen"
                            x-on:click.outside="histOpen = false"
                            class="w-6 h-6 rounded-full inline-flex items-center justify-center text-muted hover:text-ink transition-colors cursor-pointer"
                            style="background: rgba(0,0,0,0.06)"
                            title="Conversation history"
                        >
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </button>

                        <div
                            x-show="histOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-8 z-10 w-[280px] bg-white border border-line rounded-xl shadow-[0_8px_24px_rgba(0,0,0,0.12)] overflow-hidden"
                        >
                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted px-3 pt-2.5 pb-1.5">Recent conversations</p>
                            <div class="divide-y divide-hairline">
                                @foreach($recentConversations as $conv)
                                <button
                                    wire:click="switchConversation({{ $conv['id'] }})"
                                    x-on:click="histOpen = false"
                                    class="w-full text-left px-3 py-2 hover:bg-surface transition-colors cursor-pointer {{ $conv['active'] ? 'bg-accent-light' : '' }}"
                                >
                                    <p class="text-[12px] text-ink truncate">{{ $conv['preview'] }}</p>
                                    <p class="text-[10px] text-muted mt-0.5">{{ $conv['timestamp'] }}</p>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <button
                        wire:click="toggle"
                        class="w-6 h-6 rounded-full inline-flex items-center justify-center text-muted hover:text-ink transition-colors cursor-pointer"
                        style="background: rgba(0,0,0,0.06)"
                    >
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Row 2: inline project selector + stat pills + new chat --}}
            <div class="flex items-center gap-2 px-4 pb-3 flex-wrap">
                {{-- Inline project selector --}}
                <div class="relative shrink-0">
                    <select
                        x-on:change="$wire.selectProject($event.target.value)"
                        class="text-[12px] text-ink bg-surface border border-line rounded-md pl-2.5 pr-6 py-1 appearance-none cursor-pointer outline-none focus:border-accent transition-colors max-w-[180px]"
                    >
                        <option value="">Project…</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected($projectId == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 text-muted">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                </div>

                {{-- Stat pills — highlight when context enriched --}}
                @if($projectId)
                @php
                    $selSprints = count($selectedSprintIds);
                    $selTasks   = count($selectedTaskIds);
                    $selBacklog = count($selectedBacklogIds);
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $selSprints > 0 ? 'bg-blue-50 text-blue-700' : 'bg-surface text-muted' }}">
                    @if($selSprints > 0){{ $selSprints }}/{{ $activeSprintsCount }}@else{{ $activeSprintsCount }}@endif sprint{{ $activeSprintsCount !== 1 ? 's' : '' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $selTasks > 0 ? 'bg-green-50 text-green-700' : 'bg-surface text-muted' }}">
                    @if($selTasks > 0){{ $selTasks }}/{{ $tasksCount }}@else{{ $tasksCount }}@endif task{{ $tasksCount !== 1 ? 's' : '' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $selBacklog > 0 ? 'bg-amber-50 text-amber-700' : 'bg-surface text-muted' }}">
                    @if($selBacklog > 0){{ $selBacklog }}/{{ $backlogCount }}@else{{ $backlogCount }}@endif backlog
                </span>
                @if(! $projectGuide)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#fef9ec] text-[#9a7a1a]">
                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    No guide
                </span>
                @endif

                {{-- New chat button —— calls PHP method to create fresh conversation --}}
                <button
                    wire:click="newChat"
                    class="ml-auto inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium text-muted hover:text-ink hover:bg-surface border border-line transition-colors cursor-pointer"
                    title="New conversation"
                >
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New chat
                </button>
                @endif
            </div>
        </div>

        {{-- ── Context (collapsible — sprints, tasks, backlog, file) ─────── --}}
        @if($projectId)
        <div
            x-data="{
                open: false,
                sprintsOpen: true,
                tasksOpen: false,
                backlogOpen: false,
                taskSearch: '',
                backlogSearch: '',
                showMoreTasks: false,
                showMoreBacklog: false,
                allTasks: @js($availableTasks),
                allBacklog: @js($availableBacklogItems),
                selectedTaskIds: @entangle('selectedTaskIds').live,
                selectedBacklogIds: @entangle('selectedBacklogIds').live,
                get filteredTasks() {
                    const q = this.taskSearch.toLowerCase();
                    return q ? this.allTasks.filter(t => t.title.toLowerCase().includes(q)) : this.allTasks;
                },
                get filteredBacklog() {
                    const q = this.backlogSearch.toLowerCase();
                    return q ? this.allBacklog.filter(b => b.title.toLowerCase().includes(q)) : this.allBacklog;
                },
                get visibleTasks() {
                    return this.showMoreTasks ? this.filteredTasks : this.filteredTasks.slice(0, 10);
                },
                get visibleBacklog() {
                    return this.showMoreBacklog ? this.filteredBacklog : this.filteredBacklog.slice(0, 10);
                },
                toggleTask(id) {
                    const i = this.selectedTaskIds.indexOf(id);
                    i >= 0 ? this.selectedTaskIds.splice(i, 1) : this.selectedTaskIds.push(id);
                },
                toggleBacklog(id) {
                    const i = this.selectedBacklogIds.indexOf(id);
                    i >= 0 ? this.selectedBacklogIds.splice(i, 1) : this.selectedBacklogIds.push(id);
                },
                statusColor(s) {
                    const map = { active: '#15803d', draft: '#6b7280', completed: '#1d4ed8', done: '#15803d', in_progress: '#1d4ed8', todo: '#6b7280', refined: '#1d4ed8', raw: '#6b7280' };
                    return map[s] ?? '#6b7280';
                },
                priorityColor(p) {
                    return p === 'high' ? '#b94040' : p === 'medium' ? '#9a7a1a' : '#6b7280';
                }
            }"
            class="shrink-0 bg-white border-b border-hairline"
        >
            <div class="flex items-center px-4 py-1.5">
                <button
                    x-on:click="open = !open"
                    class="text-[11px] font-medium text-accent hover:underline cursor-pointer transition-colors"
                    x-text="open ? '− Hide context' : '+ Add context'"
                ></button>
            </div>

            <div x-show="open" x-cloak x-transition class="px-4 pb-3 space-y-3">

                {{-- Sprints subsection --}}
                @if(count($availableSprints) > 0)
                <div>
                    <button
                        x-on:click="sprintsOpen = !sprintsOpen"
                        class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-muted w-full text-left mb-1.5 hover:text-ink transition-colors"
                    >
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                            x-bind:style="sprintsOpen ? 'transform:rotate(90deg)' : ''" style="transition:transform 0.15s">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                        Sprints
                    </button>
                    <div x-show="sprintsOpen" x-cloak class="flex flex-col gap-0.5">
                        @foreach($availableSprints as $sprint)
                        @php
                            $sBadge = match($sprint['status']) {
                                'active'    => 'bg-green-50 text-green-700',
                                'draft'     => 'bg-gray-100 text-gray-500',
                                'completed' => 'bg-blue-50 text-blue-700',
                                default     => 'bg-gray-100 text-gray-500',
                            };
                        @endphp
                        <label class="flex items-center gap-2 cursor-pointer py-0.5 group">
                            <input
                                type="checkbox"
                                wire:model.live="selectedSprintIds"
                                value="{{ $sprint['id'] }}"
                                class="rounded cursor-pointer accent-accent shrink-0"
                            >
                            <span class="flex-1 text-[12px] text-ink truncate group-hover:text-accent transition-colors">{{ $sprint['name'] }}</span>
                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium {{ $sBadge }}">{{ $sprint['status'] }}</span>
                            <span class="text-[10px] text-muted shrink-0">{{ $sprint['task_count'] }}t</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Tasks subsection --}}
                @if(count($availableTasks) > 0)
                <div>
                    <button
                        x-on:click="tasksOpen = !tasksOpen"
                        class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-muted w-full text-left hover:text-ink transition-colors"
                    >
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                            x-bind:style="tasksOpen ? 'transform:rotate(90deg)' : ''" style="transition:transform 0.15s">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                        Tasks
                    </button>
                    <div x-show="tasksOpen" x-cloak class="mt-1.5 space-y-1.5">
                        <input
                            type="search"
                            x-model="taskSearch"
                            placeholder="Search tasks…"
                            class="w-full text-[12px] text-ink bg-surface border border-line rounded-md px-2.5 py-1 outline-none focus:border-accent transition-colors placeholder:text-muted"
                        >
                        <div class="flex flex-col gap-0.5">
                            <template x-for="task in visibleTasks" :key="task.id">
                                <label class="flex items-center gap-2 cursor-pointer py-0.5 group">
                                    <input
                                        type="checkbox"
                                        x-bind:checked="selectedTaskIds.includes(task.id)"
                                        x-on:change="toggleTask(task.id)"
                                        class="rounded cursor-pointer accent-accent shrink-0"
                                    >
                                    <span x-text="task.title" class="flex-1 text-[12px] text-ink truncate group-hover:text-accent transition-colors"></span>
                                    <span
                                        x-text="task.status.replace('_', ' ')"
                                        x-bind:style="'color:' + statusColor(task.status)"
                                        class="text-[10px] font-medium shrink-0"
                                    ></span>
                                    <span
                                        x-text="task.priority"
                                        x-bind:style="'color:' + priorityColor(task.priority)"
                                        class="text-[10px] font-medium shrink-0"
                                    ></span>
                                </label>
                            </template>
                        </div>
                        <button
                            x-show="filteredTasks.length > 10 && !showMoreTasks"
                            x-on:click="showMoreTasks = true; taskSearch = ''"
                            class="text-[11px] text-accent hover:underline cursor-pointer"
                            x-text="'Show ' + (filteredTasks.length - 10) + ' more…'"
                        ></button>
                    </div>
                </div>
                @endif

                {{-- Backlog subsection --}}
                @if(count($availableBacklogItems) > 0)
                <div>
                    <button
                        x-on:click="backlogOpen = !backlogOpen"
                        class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-muted w-full text-left hover:text-ink transition-colors"
                    >
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                            x-bind:style="backlogOpen ? 'transform:rotate(90deg)' : ''" style="transition:transform 0.15s">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                        Backlog items
                    </button>
                    <div x-show="backlogOpen" x-cloak class="mt-1.5 space-y-1.5">
                        <input
                            type="search"
                            x-model="backlogSearch"
                            placeholder="Search backlog…"
                            class="w-full text-[12px] text-ink bg-surface border border-line rounded-md px-2.5 py-1 outline-none focus:border-accent transition-colors placeholder:text-muted"
                        >
                        <div class="flex flex-col gap-0.5">
                            <template x-for="item in visibleBacklog" :key="item.id">
                                <label class="flex items-center gap-2 cursor-pointer py-0.5 group">
                                    <input
                                        type="checkbox"
                                        x-bind:checked="selectedBacklogIds.includes(item.id)"
                                        x-on:change="toggleBacklog(item.id)"
                                        class="rounded cursor-pointer accent-accent shrink-0"
                                    >
                                    <span x-text="item.title" class="flex-1 text-[12px] text-ink truncate group-hover:text-accent transition-colors"></span>
                                    <span
                                        x-text="item.status"
                                        x-bind:style="'color:' + statusColor(item.status)"
                                        class="text-[10px] font-medium shrink-0"
                                    ></span>
                                </label>
                            </template>
                        </div>
                        <button
                            x-show="filteredBacklog.length > 10 && !showMoreBacklog"
                            x-on:click="showMoreBacklog = true; backlogSearch = ''"
                            class="text-[11px] text-accent hover:underline cursor-pointer"
                            x-text="'Show ' + (filteredBacklog.length - 10) + ' more…'"
                        ></button>
                    </div>
                </div>
                @endif

                {{-- File upload --}}
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Upload context file</p>
                    <input
                        type="file"
                        accept=".md,.txt"
                        class="w-full cursor-pointer text-[12px] text-dim"
                        x-on:change="
                            const file = $event.target.files[0];
                            if (!file) return;
                            const reader = new FileReader();
                            reader.onload = e => $wire.setAdditionalContext(e.target.result);
                            reader.readAsText(file);
                        "
                    >
                    @if($additionalContext)
                    <p class="text-[11px] text-[#2e7d55] mt-1">File loaded ({{ strlen($additionalContext) }} chars)</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── Messages area ────────────────────────────────────────────────── --}}
        <div
            class="flex-1 overflow-y-auto px-4 py-4 bg-white"
            x-ref="messages"
        >
            @if(empty($messages) && ! $projectId)
                <div class="h-full flex flex-col items-center justify-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center bg-surface">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <p class="text-[13px] text-muted text-center max-w-[200px] leading-relaxed">Select a project and start chatting.</p>
                </div>

            @elseif(empty($messages) && $projectId)
                @php $proj = $projects->firstWhere('id', $projectId); @endphp
                <div class="h-full flex flex-col items-center justify-center gap-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center bg-accent-light">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-accent">
                            <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/>
                        </svg>
                    </div>
                    <p class="text-[13px] text-muted text-center max-w-[220px] leading-relaxed">
                        Ask anything about <strong class="text-dim font-semibold">{{ $proj?->name ?? 'this project' }}</strong>
                    </p>
                    {{-- Suggested prompts --}}
                    <div class="flex flex-col gap-1.5 w-full max-w-[280px]">
                        @foreach([
                            'Suggest tasks for the current sprint',
                            'What\'s the status of this project?',
                            'Help me plan the next sprint',
                        ] as $prompt)
                        <button
                            wire:click="quickPrompt('{{ addslashes($prompt) }}')"
                            class="text-left text-[12px] text-accent border border-accent/30 bg-accent-light hover:bg-accent hover:text-white rounded-lg px-3 py-2 transition-colors cursor-pointer leading-snug"
                        >{{ $prompt }}</button>
                        @endforeach
                    </div>
                </div>

            @else
                <div class="space-y-3">
                    @foreach($messages as $message)
                    <div
                        wire:key="msg-{{ $message['id'] ?? $loop->index }}"
                        class="flex flex-col {{ $message['role'] === 'user' ? 'items-end' : 'items-start w-full' }}"
                    >
                        @if($message['role'] === 'user')
                            <div class="rounded-2xl rounded-tr-sm px-3.5 py-2 bg-accent text-white max-w-[82%]">
                                <p class="text-[13px] leading-relaxed m-0 whitespace-pre-wrap">{{ $message['content'] }}</p>
                            </div>
                            <span class="text-[10px] text-muted mt-0.5 px-0.5">
                                {{ \Carbon\Carbon::parse($message['created_at'])->format('H:i') }}
                            </span>
                        @else
                            <div class="w-full px-2 py-1 text-ink">
                                <div class="inline-flex items-center gap-1 text-[10px] text-muted mb-1">
                                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/>
                                    </svg>
                                    <span>AI</span>
                                </div>
                                <div class="prose prose-sm max-w-none text-[13px]">
                                    {!! \Illuminate\Support\Str::markdown(
                                        preg_replace('/<actions>.*?<\/actions>/s', '', $message['content'])
                                    ) !!}
                                </div>
                                <div class="text-right text-[10px] text-muted mt-1">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('H:i') }}
                                </div>
                            </div>

                            {{-- ── Interactive question card ─────────────────── --}}
                            @if(! empty($message['actions']) && (($message['actions']['type'] ?? null) === 'question'))
                            @php
                                $qAction    = $message['actions'];
                                $qType      = $qAction['input_type'] ?? 'pills';
                                $qOptions   = is_array($qAction['options'] ?? null) ? $qAction['options'] : [];
                                $qFields    = is_array($qAction['form'] ?? null) ? $qAction['form'] : [];
                                $msgDbId    = $message['id'];
                                $isAnswered = ! empty($qAction['answered']);
                            @endphp
                            <div
                                class="mt-2 w-full"
                                x-data="{ answered: {{ $isAnswered ? 'true' : 'false' }}, otherOpen: false, textAnswer: '', selected: [], formValues: {} }"
                            >
                                <div
                                    x-show="!answered"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    class="bg-white border border-line rounded-lg p-3"
                                >
                                    <p class="text-[14px] font-semibold text-ink leading-snug mb-2">
                                        {{ $qAction['question'] ?? 'Please provide more details.' }}
                                    </p>

                                    @if($qType === 'text')
                                    <div class="flex items-center gap-2">
                                        <input
                                            x-model="textAnswer"
                                            x-on:keydown.enter.prevent="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()); }"
                                            class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent"
                                            placeholder="Type your answer..."
                                        >
                                        <button
                                            x-on:click="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()); }"
                                            class="px-2.5 py-1.5 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer"
                                        >Send</button>
                                    </div>

                                    @elseif($qType === 'multiselect')
                                    <div class="space-y-2">
                                        <div class="space-y-1.5">
                                            @foreach($qOptions as $opt)
                                            <label class="flex items-center gap-2 text-[13px] text-dim cursor-pointer">
                                                <input type="checkbox" value="{{ $opt }}" x-model="selected" class="rounded accent-accent">
                                                <span>{{ $opt }}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                        <button
                                            x-on:click="if (selected.length > 0) { answered = true; $wire.answerQuestion({{ $msgDbId }}, selected.join(', ')); }"
                                            class="px-2.5 py-1.5 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer"
                                        >Confirm</button>
                                    </div>

                                    @elseif($qType === 'form')
                                    <div class="space-y-2.5">
                                        @foreach($qFields as $fIdx => $field)
                                        @php
                                            $fieldName  = (string) ($field['name'] ?? ('field_' . $fIdx));
                                            $fieldLabel = (string) ($field['label'] ?? ucwords(str_replace('_', ' ', $fieldName)));
                                            $fieldType  = (string) ($field['type'] ?? $field['input_type'] ?? 'text');
                                            $fieldOpts  = is_array($field['options'] ?? null) ? $field['options'] : [];
                                        @endphp
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">{{ $fieldLabel }}</label>
                                            @if($fieldType === 'select' || $fieldType === 'pills')
                                            <select x-model="formValues['{{ $fieldName }}']" class="w-full text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent">
                                                <option value="">Select...</option>
                                                @foreach($fieldOpts as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                            @elseif($fieldType === 'textarea')
                                            <textarea x-model="formValues['{{ $fieldName }}']" rows="2" class="w-full text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent resize-none" placeholder="{{ $fieldLabel }}"></textarea>
                                            @else
                                            <input x-model="formValues['{{ $fieldName }}']" type="text" class="w-full text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent" placeholder="{{ $fieldLabel }}">
                                            @endif
                                        </div>
                                        @endforeach
                                        <button
                                            x-on:click="const payload = Object.entries(formValues).filter(([_, v]) => String(v).trim() !== '').map(([k, v]) => `${k}: ${v}`).join(', '); if (payload !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, payload); }"
                                            class="px-2.5 py-1.5 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer"
                                        >Send</button>
                                    </div>

                                    @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($qOptions as $opt)
                                        <button
                                            x-on:click="answered = true; $wire.answerQuestion({{ $msgDbId }}, {{ \Illuminate\Support\Js::from($opt) }});"
                                            class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-surface text-dim border border-line hover:border-accent hover:text-accent transition-colors cursor-pointer"
                                        >{{ $opt }}</button>
                                        @endforeach
                                    </div>
                                    @if(! empty($qAction['allow_custom']))
                                    <div class="mt-2">
                                        <button x-on:click="otherOpen = !otherOpen" class="text-[11px] text-accent hover:underline cursor-pointer">Other...</button>
                                        <div x-show="otherOpen" x-cloak class="mt-1.5 flex items-center gap-2">
                                            <input
                                                x-model="textAnswer"
                                                x-on:keydown.enter.prevent="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()); }"
                                                class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent"
                                                placeholder="Type custom answer..."
                                            >
                                            <button
                                                x-on:click="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()); }"
                                                class="px-2.5 py-1.5 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer"
                                            >Send</button>
                                        </div>
                                    </div>
                                    @endif
                                    @endif
                                </div>

                                <div x-show="answered" x-cloak class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[11px] font-medium bg-[#edf7f2] text-[#2e7d55] border border-hairline">
                                    <span>✓</span>
                                    <span>Answered</span>
                                </div>
                            </div>

                            {{-- ── Action cards ─────────────────────────────── --}}
                            @elseif(! empty($message['actions']['items']))
                            @php
                                $actType  = $message['actions']['type'] ?? 'backlog';
                                $actItems = $message['actions']['items'];
                                $msgDbId  = $message['id'];
                            @endphp
                            <div class="mt-2 space-y-2 w-full">
                                @foreach($actItems as $aIdx => $action)
                                <div
                                    wire:key="action-{{ $msgDbId }}-{{ $aIdx }}"
                                    x-data="{
                                        title: @js($action['title'] ?? ''),
                                        description: @js($action['description'] ?? ''),
                                        itemType: @js($action['type'] ?? 'feature'),
                                        weight: {{ (int) ($action['weight'] ?? 2) }},
                                        priority: @js($action['priority'] ?? 'medium'),
                                        sprintId: '',
                                        confirmed: {{ ($action['confirmed'] ?? false) ? 'true' : 'false' }},
                                        skipped: {{ ($action['skipped'] ?? false) ? 'true' : 'false' }},
                                        editTitle: false,
                                        editDesc: false,
                                        init() {
                                            this.$watch('editTitle', v => { if (v) this.$nextTick(() => this.$refs.titleInput && this.$refs.titleInput.focus()); });
                                        }
                                    }"
                                    x-show="!skipped"
                                    x-cloak
                                    class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden"
                                >
                                    {{-- Confirmed state --}}
                                    <div x-show="confirmed" class="flex items-center gap-2 px-3 py-2 bg-[#f0faf5] border-l-[3px] border-[#2e7d55]">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-[#2e7d55] shrink-0">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        <span class="text-[12px] font-medium text-[#2e7d55]">Created</span>
                                        <span x-text="title" class="text-[12px] text-dim truncate"></span>
                                    </div>

                                    {{-- Active card --}}
                                    <div x-show="!confirmed" class="p-3">

                                        {{-- Top row: type icon + editable title + skip --}}
                                        <div class="flex items-center gap-1.5 mb-2">
                                            @if($actType === 'sprints')
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-accent shrink-0">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                            </svg>
                                            @elseif($actType === 'tasks')
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-accent shrink-0">
                                                <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                            </svg>
                                            @else
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-accent shrink-0">
                                                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                                            </svg>
                                            @endif

                                            <span
                                                x-show="!editTitle"
                                                x-text="title"
                                                x-on:click="editTitle = true"
                                                class="flex-1 text-[13px] font-medium text-ink cursor-text min-w-0 truncate"
                                            ></span>
                                            <input
                                                x-show="editTitle"
                                                x-model="title"
                                                x-ref="titleInput"
                                                x-on:blur="editTitle = false"
                                                x-on:keydown.enter="editTitle = false"
                                                class="flex-1 text-[13px] font-medium text-ink bg-transparent outline-none border-b border-accent min-w-0"
                                            >
                                            <button
                                                x-on:click="skipped = true; $wire.skipAction({{ $msgDbId }}, {{ $aIdx }})"
                                                class="text-[10px] text-muted hover:text-ink shrink-0 cursor-pointer ml-1 leading-none"
                                            >Skip</button>
                                        </div>

                                        {{-- Badges + weight (tasks / backlog only) --}}
                                        @if($actType !== 'sprints')
                                        <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                                            <select
                                                x-model="itemType"
                                                class="text-[10px] text-dim bg-surface border border-line rounded px-1.5 py-0.5 appearance-none outline-none cursor-pointer"
                                            >
                                                <option value="feature">feature</option>
                                                <option value="bug">bug</option>
                                                <option value="change">change</option>
                                            </select>
                                            <select
                                                x-model="priority"
                                                class="text-[10px] text-dim bg-surface border border-line rounded px-1.5 py-0.5 appearance-none outline-none cursor-pointer"
                                            >
                                                <option value="low">low</option>
                                                <option value="medium">medium</option>
                                                <option value="high">high</option>
                                            </select>
                                            {{-- Weight 1-5 boxes --}}
                                            <div class="flex items-center gap-0.5">
                                                @for($w = 1; $w <= 5; $w++)
                                                <button
                                                    x-on:click="weight = {{ $w }}"
                                                    x-bind:class="weight >= {{ $w }} ? 'bg-accent' : 'bg-gray-200'"
                                                    class="w-3 h-3 rounded-sm cursor-pointer transition-colors"
                                                    title="Weight {{ $w }}"
                                                ></button>
                                                @endfor
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Description (editable) --}}
                                        @if(! empty($action['description']))
                                        <div class="mb-2">
                                            <p
                                                x-show="!editDesc"
                                                x-text="description"
                                                x-on:click="editDesc = true"
                                                class="text-[11px] text-muted leading-relaxed cursor-text"
                                            ></p>
                                            <textarea
                                                x-show="editDesc"
                                                x-model="description"
                                                x-on:blur="editDesc = false"
                                                rows="2"
                                                class="w-full text-[11px] text-muted bg-gray-50 rounded px-2 py-1 outline-none resize-none border border-gray-100"
                                            ></textarea>
                                        </div>
                                        @endif

                                        {{-- Bottom row: sprint selector + action buttons --}}
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if($actType !== 'sprints' && count($activeSprints) > 0)
                                            <select
                                                x-model="sprintId"
                                                class="text-[10px] text-dim bg-surface border border-line rounded px-1.5 py-0.5 appearance-none outline-none cursor-pointer max-w-[120px]"
                                            >
                                                <option value="">No sprint</option>
                                                @foreach($activeSprints as $sp)
                                                <option value="{{ $sp['id'] }}">{{ $sp['name'] }}</option>
                                                @endforeach
                                            </select>
                                            @endif

                                            @if($actType === 'sprints')
                                            <button
                                                x-on:click="confirmed = true; $wire.confirmAction({{ $msgDbId }}, {{ $aIdx }}, { title, description, targetAction: 'sprint_create' })"
                                                class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover cursor-pointer transition-colors"
                                            >Create sprint</button>
                                            @else
                                            <button
                                                x-on:click="confirmed = true; $wire.confirmAction({{ $msgDbId }}, {{ $aIdx }}, { title, description, type: itemType, weight, priority, sprintId, targetAction: (sprintId !== '') ? 'sprint' : 'backlog' })"
                                                class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover cursor-pointer transition-colors"
                                            >
                                                @if($actType === 'tasks')
                                                <span x-text="sprintId !== '' ? 'Add to sprint' : 'Add to backlog'"></span>
                                                @else
                                                Add to backlog
                                                @endif
                                            </button>
                                            @if($actType === 'tasks')
                                            <button
                                                x-show="sprintId !== ''"
                                                x-on:click="confirmed = true; $wire.confirmAction({{ $msgDbId }}, {{ $aIdx }}, { title, description, type: itemType, weight, priority, sprintId: '', targetAction: 'backlog' })"
                                                class="px-2.5 py-1 rounded-md text-[11px] font-medium border border-line text-dim hover:text-ink hover:bg-surface cursor-pointer transition-colors"
                                            >Add to backlog</button>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach

                                {{-- Create all button (when more than one item and any unconfirmed) --}}
                                @if(count($actItems) > 1)
                                @php $hasUnconfirmed = collect($actItems)->contains(fn ($i) => empty($i['confirmed']) && empty($i['skipped'])); @endphp
                                @if($hasUnconfirmed)
                                <div class="flex justify-end">
                                    <button
                                        wire:click="confirmAllActions({{ $msgDbId }})"
                                        class="text-[11px] font-medium text-accent hover:underline cursor-pointer"
                                    >Create all →</button>
                                </div>
                                @endif
                                @endif
                            </div>
                            @endif
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif

            {{-- ── Streaming bubble ────────────────────────────────────────── --}}
            <div x-show="isStreaming" style="display: none" class="flex flex-col items-start mt-3">
                <div class="rounded-2xl rounded-tl-sm bg-gray-50 border border-gray-100 text-ink max-w-[82%] p-3">
                    <div x-show="streamingText === ''" class="flex items-center gap-1 py-0.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-muted" style="animation: bounce 1s infinite 0ms"></span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-muted" style="animation: bounce 1s infinite 150ms"></span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-muted" style="animation: bounce 1s infinite 300ms"></span>
                    </div>
                    <p x-show="streamingText !== ''" x-text="streamingText" class="text-[13px] leading-relaxed m-0 whitespace-pre-wrap"></p>
                </div>
            </div>

            {{-- ── Stream error ─────────────────────────────────────────────── --}}
            <div x-show="streamingError !== ''" style="display: none" class="flex justify-center mt-3">
                <span x-text="streamingError" class="text-[12px] text-[#b94040] bg-[#fff5f5] border border-[#f5c6c6] rounded-lg px-3 py-1.5"></span>
            </div>
        </div>

        {{-- ── Input area — unified container ─────────────────────────────── --}}
        <div class="shrink-0 p-3 bg-white border-t border-line">
            <div class="border border-gray-200 rounded-xl p-2 focus-within:border-accent transition-colors">

                {{-- Context pills row --}}
                @php $hasPills = ! empty($selectedSprintIds) || ! empty($selectedTaskIds) || ! empty($selectedBacklogIds) || $additionalContext !== ''; @endphp
                @if($hasPills)
                <div class="flex flex-wrap gap-1 mb-1.5 px-1">
                    {{-- Sprint pills (blue) --}}
                    @foreach($availableSprints as $sprint)
                        @if(in_array($sprint['id'], $selectedSprintIds))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700">
                            {{ $sprint['name'] }}
                            <button
                                wire:click="removeSprintContext({{ $sprint['id'] }})"
                                class="cursor-pointer hover:opacity-70 transition-opacity leading-none"
                            >&times;</button>
                        </span>
                        @endif
                    @endforeach
                    {{-- Task pills (green) --}}
                    @foreach($availableTasks as $task)
                        @if(in_array($task['id'], $selectedTaskIds))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-50 text-green-700">
                            {{ \Illuminate\Support\Str::limit($task['title'], 20) }}
                            <button
                                wire:click="removeTaskContext({{ $task['id'] }})"
                                class="cursor-pointer hover:opacity-70 transition-opacity leading-none"
                            >&times;</button>
                        </span>
                        @endif
                    @endforeach
                    {{-- Backlog pills (amber) --}}
                    @foreach($availableBacklogItems as $item)
                        @if(in_array($item['id'], $selectedBacklogIds))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700">
                            {{ \Illuminate\Support\Str::limit($item['title'], 20) }}
                            <button
                                wire:click="removeBacklogContext({{ $item['id'] }})"
                                class="cursor-pointer hover:opacity-70 transition-opacity leading-none"
                            >&times;</button>
                        </span>
                        @endif
                    @endforeach
                    {{-- File pill --}}
                    @if($additionalContext !== '')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#edf7f2] text-[#2e7d55]">
                        File
                        <button wire:click="removeAdditionalContext" class="cursor-pointer hover:opacity-70 transition-opacity leading-none">&times;</button>
                    </span>
                    @endif
                </div>
                @endif

                {{-- Textarea + send button --}}
                <div class="flex items-end gap-1">
                    <textarea
                        wire:model="input"
                        rows="2"
                        placeholder="{{ $projectId ? 'Ask about this project…' : 'Select a project first…' }}"
                        class="flex-1 text-[13px] text-ink bg-transparent outline-none resize-none placeholder:text-muted placeholder:italic px-1"
                        style="max-height: 120px; line-height: 1.5"
                        x-on:keydown.enter="if (!$event.shiftKey && !isStreaming) { $event.preventDefault(); $wire.sendMessage(); }"
                        x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                        x-bind:disabled="!projectIdState || isStreaming"
                    ></textarea>

                    <button
                        wire:click="sendMessage"
                        x-bind:disabled="isStreaming || !projectIdState || inputText.trim().length === 0"
                        x-bind:class="(!isStreaming && projectIdState && inputText.trim().length > 0)
                            ? 'bg-accent hover:bg-accent-hover text-white cursor-pointer'
                            : 'bg-surface text-muted cursor-not-allowed opacity-50'"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg shrink-0 transition-colors duration-150"
                        title="Send (Enter)"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
