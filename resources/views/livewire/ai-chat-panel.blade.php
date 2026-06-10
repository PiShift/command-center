{{-- AI Chat Panel — Livewire component --}}
<div
    x-data="{
        open: @entangle('isOpen').live,
        isStreaming: @entangle('isStreaming').live,
        inputText: @entangle('input').live,
        projectIdState: @entangle('projectId').live,
        streamingText: '',
        streamingError: '',
        parsedActionsPayload: null,
        streamReader: null,
        streamStopRequested: false,
        rawStreamBuffer: '',
        visibleStreamBuffer: '',
        hiddenActionsBuffer: '',
        inActionsTag: false,
        userScrolled: false,
        onMessagesScroll() {
            const el = this.$refs.messages;
            if (!el) return;
            const threshold = 50;
            const atBottom = el.scrollTop >= (el.scrollHeight - el.clientHeight - threshold);
            this.userScrolled = !atBottom;
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el && !this.userScrolled) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },
        escapeHtml(value) {
            return String(value)
                .replace(/&/g, '\x26amp;')
                .replace(/</g, '\x26lt;')
                .replace(/>/g, '\x26gt;')
                .replace(/\x22/g, '\x26quot;')
                .replace(/\x27/g, '\x26#39;');
        },
        inlineStreamingMarkdown(value) {
            const escaped = this.escapeHtml(value);
            return escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        },
        renderStreamingMarkdown(value) {
            const lines = String(value || '').split(/\r?\n/);
            const html = lines.map((line) => {
                if (/^###\s+/.test(line)) {
                    return '<p style=\'font-size:15px;font-weight:600;color:var(--color-ink,#141413);line-height:1.2;margin:2px 0\'>' + this.inlineStreamingMarkdown(line.replace(/^###\s+/, '')) + '</p>';
                }
                if (/^[-*]\s+/.test(line)) {
                    return '<p style=\'font-size:13px;color:var(--color-ink,#141413);line-height:1.5;margin:0\'>\u2022 ' + this.inlineStreamingMarkdown(line.replace(/^[-*]\s+/, '')) + '</p>';
                }
                if (line.trim() === '') {
                    return '<p style=\'font-size:13px;line-height:1.5;margin:0\'>\x26nbsp;</p>';
                }
                return '<p style=\'font-size:13px;color:var(--color-ink,#141413);line-height:1.5;margin:0\'>' + this.inlineStreamingMarkdown(line) + '</p>';
            }).join('');
            return html;
        },
        processChunkVisibility(chunk) {
            this.rawStreamBuffer += String(chunk || '');

            let i = 0;
            let visible = '';
            let hidden = '';
            let inActions = false;

            while (i < this.rawStreamBuffer.length) {
                if (!inActions && this.rawStreamBuffer.startsWith('<actions>', i)) {
                    inActions = true;
                    hidden += '<actions>';
                    i += 9;
                    continue;
                }

                if (inActions && this.rawStreamBuffer.startsWith('</actions>', i)) {
                    inActions = false;
                    hidden += '</actions>';
                    i += 10;
                    continue;
                }

                const ch = this.rawStreamBuffer[i];
                if (inActions) {
                    hidden += ch;
                } else {
                    visible += ch;
                }
                i += 1;
            }

            this.inActionsTag = inActions;
            this.visibleStreamBuffer = visible;
            this.hiddenActionsBuffer = hidden;
            this.streamingText = visible;
        },
        resetStreamState() {
            this.streamingText = '';
            this.streamingError = '';
            this.parsedActionsPayload = null;
            this.streamStopRequested = false;
            this.rawStreamBuffer = '';
            this.visibleStreamBuffer = '';
            this.hiddenActionsBuffer = '';
            this.inActionsTag = false;
        },
        async stopStreaming() {
            if (!this.isStreaming) return;

            this.streamStopRequested = true;

            if (this.streamReader) {
                try {
                    await this.streamReader.cancel();
                } catch (_) {}
            }

            let partial = this.rawStreamBuffer.trim();
            partial = partial !== '' ? `${partial}\n\n_(response stopped)_` : '_(response stopped)_';

            this.streamingText = '';
            this.isStreaming = false;
            $wire.set('isStreaming', false);

            await $wire.saveAssistantMessage(partial);

            this.parsedActionsPayload = null;
            this.$nextTick(() => {
                this.scrollToBottom();
                this.$refs.chatInput && this.$refs.chatInput.focus();
            });
        },
        async streamMessage(detail) {
            this.resetStreamState();
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
                        status_snapshot: detail.statusSnapshot,
                    }),
                });
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                const reader  = response.body.getReader();
                this.streamReader = reader;
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
                            if (this.streamStopRequested) {
                                this.streamReader = null;
                                return;
                            }

                            const textToSave = this.rawStreamBuffer;
                            this.streamingText = '';
                            if (this.parsedActionsPayload?.actions) {
                                window.dispatchEvent(new CustomEvent('actions-parsed', { detail: this.parsedActionsPayload }));
                            }
                            await $wire.saveAssistantMessage(textToSave);
                            this.parsedActionsPayload = null;
                            this.streamReader = null;
                            this.$nextTick(() => {
                                this.scrollToBottom();
                                this.$refs.chatInput && this.$refs.chatInput.focus();
                            });
                            return;
                        }
                        try {
                            const parsed = JSON.parse(payload);
                            if (parsed.chunk) {
                                this.processChunkVisibility(parsed.chunk);
                                this.$nextTick(() => this.scrollToBottom());
                            } else if (parsed.actions_parsed?.actions) {
                                this.parsedActionsPayload = parsed.actions_parsed;
                            }
                        } catch (_) {}
                    }
                }
                this.streamReader = null;
                if (!this.streamStopRequested && this.rawStreamBuffer) {
                    const textToSave = this.rawStreamBuffer;
                    this.streamingText = '';
                    if (this.parsedActionsPayload?.actions) {
                        window.dispatchEvent(new CustomEvent('actions-parsed', { detail: this.parsedActionsPayload }));
                    }
                    await $wire.saveAssistantMessage(textToSave);
                    this.parsedActionsPayload = null;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        this.$refs.chatInput && this.$refs.chatInput.focus();
                    });
                }
            } catch (err) {
                this.streamReader = null;
                if (this.streamStopRequested) {
                    return;
                }
                console.error('AI stream error:', err);
                this.streamingError = 'Connection error. Please try again.';
                $wire.set('isStreaming', false);
            }
        },
        captureParsedActions(detail) {
            if (!detail?.actions) return;
            $wire.captureParsedActions(detail.actions);
        }
    }"
    x-init="
        $watch('open', val => { if (val) scrollToBottom() });
        $wire.$watch('messages', () => scrollToBottom());
    "
    x-on:open-ai-chat.window="$wire.handleOpenEvent($event.detail?.projectId ?? null)"
    x-on:begin-stream.window="streamMessage($event.detail)"
    x-on:actions-parsed.window="captureParsedActions($event.detail)"
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
                    $selDocs           = count($selectedDocumentIds);
                    $activePresetCount = count($activePresets);
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $selDocs > 0 ? 'bg-indigo-50 text-indigo-700' : 'bg-surface text-muted' }}">
                    @if($selDocs > 0){{ $selDocs }}/{{ $documentsCount }}@else{{ $documentsCount }}@endif doc{{ $documentsCount !== 1 ? 's' : '' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $activePresetCount > 0 ? 'bg-blue-50 text-blue-700' : 'bg-surface text-muted' }}">
                    @if($activePresetCount > 0){{ $activePresetCount }} preset{{ $activePresetCount !== 1 ? 's' : '' }}@else{{ $activeSprintsCount }} sprint{{ $activeSprintsCount !== 1 ? 's' : '' }}@endif
                </span>

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

        {{-- ── Context (collapsible — presets + documents + file upload) ── --}}
        @if($projectId)
        <div
            x-data="{
                open: false,
                activePresets: @entangle('activePresets').live,
                selectedDocumentIds: @entangle('selectedDocumentIds').live,
                allDocuments: @js($projectDocuments),
                togglePreset(name) {
                    const i = this.activePresets.indexOf(name);
                    i >= 0 ? this.activePresets.splice(i, 1) : this.activePresets.push(name);
                },
                isActive(name) { return this.activePresets.includes(name); }
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

                {{-- Documents subsection --}}
                @if(count($projectDocuments) > 0)
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Documents</p>
                    <div class="flex flex-col gap-0.5">
                        <template x-for="doc in allDocuments" :key="doc.id">
                            <label class="flex items-center gap-2 cursor-pointer py-0.5 group">
                                <input
                                    type="checkbox"
                                    x-bind:checked="selectedDocumentIds.includes(doc.id)"
                                    x-on:change="const i = selectedDocumentIds.indexOf(doc.id); i >= 0 ? selectedDocumentIds.splice(i, 1) : selectedDocumentIds.push(doc.id)"
                                    class="rounded cursor-pointer accent-accent shrink-0"
                                >
                                <span x-text="doc.title" class="flex-1 text-[12px] text-ink truncate group-hover:text-accent transition-colors"></span>
                                <span x-show="doc.type" x-text="doc.type" class="text-[10px] text-muted shrink-0"></span>
                            </label>
                        </template>
                    </div>
                </div>
                @endif

                {{-- Bulk context presets --}}
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Context presets</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button"
                            x-on:click="togglePreset('current_sprint')"
                            :class="isActive('current_sprint')
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-dim border-line hover:border-accent hover:text-accent'"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium border transition-colors cursor-pointer">
                            Current sprint
                        </button>
                        <button type="button"
                            x-on:click="togglePreset('active_sprints')"
                            :class="isActive('active_sprints')
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-dim border-line hover:border-accent hover:text-accent'"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium border transition-colors cursor-pointer">
                            Active sprints
                        </button>
                        <button type="button"
                            x-on:click="togglePreset('backlog')"
                            :class="isActive('backlog')
                                ? 'bg-amber-500 text-white border-amber-500'
                                : 'bg-white text-dim border-line hover:border-accent hover:text-accent'"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium border transition-colors cursor-pointer">
                            Backlog
                        </button>
                        <button type="button"
                            x-on:click="togglePreset('full_project')"
                            :class="isActive('full_project')
                                ? 'bg-accent text-white border-accent'
                                : 'bg-white text-dim border-line hover:border-accent hover:text-accent'"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium border transition-colors cursor-pointer">
                            Full project
                        </button>
                    </div>
                    <p x-show="isActive('full_project')" x-cloak
                       class="mt-1.5 text-[11px] text-amber-600 leading-snug">
                        This adds significant context — use for planning sessions.
                    </p>
                </div>

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
            x-on:scroll="onMessagesScroll()"
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
                            @php
                                $assistantContent = trim(preg_replace('/<actions>.*?<\/actions>/s', '', (string) $message['content']));
                                $isClarification = (($message['actions']['type'] ?? null) === 'clarification');
                                if ($isClarification) {
                                    $assistantContent = trim((string) (preg_split('/\R/', $assistantContent)[0] ?? ''));
                                }
                                $hasCodeBlock = str_contains($assistantContent, '```');
                                $hasLongBlock = collect(preg_split('/\R+/', $assistantContent) ?: [])
                                    ->contains(fn ($line) => mb_strlen(trim((string) $line)) > 800);
                                $isArtifact = $hasCodeBlock || $hasLongBlock;
                                preg_match('/^#{1,6}\s+(.+)$/m', $assistantContent, $headingMatch);
                                $artifactTitle = trim($headingMatch[1] ?? 'AI Response');
                                $artifactCharCount = mb_strlen($assistantContent);
                                $artifactPreview = collect(preg_split('/\R/', $assistantContent) ?: [])->take(3)->implode("\n");
                                $artifactFilename = \Illuminate\Support\Str::slug($artifactTitle !== '' ? $artifactTitle : 'ai-response') . '.md';
                            @endphp
                            <div class="w-full px-2 py-1 text-ink">
                                <div class="inline-flex items-center gap-1 text-[10px] text-muted mb-1">
                                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/>
                                    </svg>
                                    <span>AI</span>
                                </div>
                                @if($isArtifact)
                                <div
                                    x-data="{ expanded: false, fullscreen: false, copied: false, saveFormOpen: false, savedToDocs: false, saveError: '', saveTitle: {{ \Illuminate\Support\Js::from($artifactTitle) }}, saveType: 'guide', docsLink: '' }"
                                    class="border border-line rounded-lg bg-white overflow-hidden"
                                >
                                    <div class="flex items-center gap-2 px-3 py-2 border-b border-hairline bg-surface">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted shrink-0">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <span class="text-[13px] font-medium text-ink truncate">{{ $artifactTitle }}</span>
                                        <span class="text-[10px] text-muted shrink-0">{{ $artifactCharCount }} chars</span>
                                        <button
                                            x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($assistantContent) }}); copied = true; setTimeout(() => copied = false, 2000);"
                                            class="ml-auto text-[10px] text-dim hover:text-ink border border-line bg-white rounded px-2 py-0.5 transition-colors cursor-pointer"
                                            x-text="copied ? 'Copied!' : 'Copy'"
                                        ></button>
                                        <button
                                            x-on:click="const blob = new Blob([{{ \Illuminate\Support\Js::from($assistantContent) }}], { type: 'text/markdown;charset=utf-8' }); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = {{ \Illuminate\Support\Js::from($artifactFilename) }}; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);"
                                            class="text-[10px] text-dim hover:text-ink border border-line bg-white rounded px-2 py-0.5 transition-colors cursor-pointer"
                                        >Download</button>
                                        <button
                                            x-show="!savedToDocs"
                                            x-on:click="saveFormOpen = true; saveError = ''"
                                            class="text-[10px] text-dim hover:text-ink border border-line bg-white rounded px-2 py-0.5 transition-colors cursor-pointer"
                                        >Save as doc</button>
                                        <a
                                            x-show="savedToDocs"
                                            x-bind:href="docsLink"
                                            class="text-[10px] text-accent hover:underline border border-accent/30 bg-accent-light rounded px-2 py-0.5 transition-colors"
                                        >View in docs →</a>
                                        <button
                                            x-on:click="fullscreen = true"
                                            class="text-[10px] text-dim hover:text-ink border border-line bg-white rounded px-2 py-0.5 transition-colors cursor-pointer"
                                        >Expand</button>
                                    </div>

                                    <div x-show="savedToDocs" x-cloak class="px-3 py-1.5 border-b border-hairline bg-success-light text-success-text text-[11px]">
                                        Saved to project docs ✓
                                    </div>

                                    <div x-show="saveFormOpen" x-cloak class="px-3 py-2 border-b border-hairline bg-white space-y-2">
                                        <div x-show="saveError !== ''" x-text="saveError" class="text-[11px] text-danger bg-[#fff5f5] border border-[#f5c6c6] rounded-md px-2 py-1"></div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[10px] font-semibold text-muted mb-1">Title</label>
                                                <input x-model="saveTitle" type="text" class="w-full text-[12px] text-ink bg-surface border border-line rounded-md px-2 py-1.5 outline-none focus:border-accent" placeholder="Document title">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-muted mb-1">Type</label>
                                                <input x-model="saveType" type="text" class="w-full text-[12px] text-ink bg-surface border border-line rounded-md px-2 py-1.5 outline-none focus:border-accent" placeholder="guide">
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                x-on:click="saveFormOpen = false; saveError = ''"
                                                class="px-2 py-1 rounded-md text-[11px] font-medium border border-line text-dim hover:text-ink hover:bg-surface transition-colors cursor-pointer"
                                            >Cancel</button>
                                            <button
                                                x-on:click="$wire.saveAsDocument({{ \Illuminate\Support\Js::from($assistantContent) }}, saveTitle, saveType).then((res) => { if (res?.ok) { savedToDocs = true; saveError = ''; saveFormOpen = false; docsLink = res.link || (`/projects/${projectIdState}#guide`); } else { saveError = res?.error || 'Failed to save document'; } })"
                                                class="px-2 py-1 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer"
                                            >Confirm</button>
                                        </div>
                                    </div>

                                    <div class="relative px-3 py-2">
                                        <pre class="text-[12px] text-dim whitespace-pre-wrap leading-relaxed" x-show="!expanded">{{ $artifactPreview }}</pre>
                                        <div x-show="!expanded" class="absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-white to-transparent"></div>
                                        <div class="prose prose-sm max-w-none text-[13px]" x-show="expanded">
                                            {!! \Illuminate\Support\Str::markdown($assistantContent) !!}
                                        </div>
                                        <button x-on:click="expanded = !expanded" class="mt-1 text-[11px] text-accent hover:underline cursor-pointer" x-text="expanded ? 'Show less' : 'Show more'"></button>
                                    </div>

                                    <div
                                        x-show="fullscreen"
                                        x-cloak
                                        x-transition:enter="transition-opacity duration-150"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        x-transition:leave="transition-opacity duration-100"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="fixed inset-0 z-[70] bg-black/40 p-4"
                                    >
                                        <div class="mx-auto max-w-4xl h-full bg-white rounded-xl border border-line shadow-[0_20px_60px_rgba(0,0,0,0.18)] flex flex-col">
                                            <div class="flex items-center gap-2 px-4 py-3 border-b border-hairline">
                                                <span class="text-[14px] font-semibold text-ink truncate">{{ $artifactTitle }}</span>
                                                <button x-on:click="fullscreen = false" class="ml-auto w-7 h-7 rounded-full inline-flex items-center justify-center bg-surface text-muted hover:text-ink transition-colors cursor-pointer" title="Close">×</button>
                                            </div>
                                            <div class="flex-1 overflow-y-auto px-4 py-3">
                                                <div class="prose prose-sm max-w-none text-[13px]">
                                                    {!! \Illuminate\Support\Str::markdown($assistantContent) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="prose prose-sm max-w-none text-[13px]">
                                    {!! \Illuminate\Support\Str::markdown($assistantContent) !!}
                                </div>
                                @endif
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
                                            x-on:keydown.enter.prevent="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()).then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
                                            class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent"
                                            placeholder="Type your answer..."
                                        >
                                        <button
                                            x-on:click="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()).then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
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
                                            x-on:click="if (selected.length > 0) { answered = true; $wire.answerQuestion({{ $msgDbId }}, selected.join(', ')).then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
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
                                            x-on:click="const payload = Object.entries(formValues).filter(([_, v]) => String(v).trim() !== '').map(([k, v]) => `${k}: ${v}`).join(', '); if (payload !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, payload).then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
                                            class="px-2.5 py-1.5 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer"
                                        >Send</button>
                                    </div>

                                    @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($qOptions as $opt)
                                        <button
                                            x-on:click="answered = true; $wire.answerQuestion({{ $msgDbId }}, {{ \Illuminate\Support\Js::from($opt) }}).then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus()));"
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
                                                x-on:keydown.enter.prevent="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()).then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
                                                class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent"
                                                placeholder="Type custom answer..."
                                            >
                                            <button
                                                x-on:click="if (textAnswer.trim() !== '') { answered = true; $wire.answerQuestion({{ $msgDbId }}, textAnswer.trim()).then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
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

                            {{-- ── Grouped sprint + tasks action card ───────── --}}
                            @elseif(! empty($message['actions']) && (($message['actions']['type'] ?? null) === 'sprint_with_tasks'))
                            @php
                                $swtAction = $message['actions'];
                                $swtTasks  = is_array($swtAction['tasks'] ?? null) ? $swtAction['tasks'] : [];
                                $msgDbId   = $message['id'];
                            @endphp
                            <div class="mt-2 w-full"
                                 x-data="{
                                    confirmed: {{ ! empty($swtAction['confirmed']) ? 'true' : 'false' }},
                                    skipped: {{ ! empty($swtAction['skipped']) ? 'true' : 'false' }},
                                    targetMode: 'new',
                                    sprintName: @js($swtAction['sprint_name'] ?? ''),
                                    sprintDescription: @js($swtAction['sprint_description'] ?? ''),
                                    existingSprintId: '',
                                    tasks: @js($swtTasks),
                                    normalizeTasks() {
                                        this.tasks = (Array.isArray(this.tasks) ? this.tasks : []).map(t => ({
                                            title: t?.title ?? '',
                                            description: t?.description ?? '',
                                            type: ['feature','bug','change'].includes(t?.type) ? t.type : 'feature',
                                            priority: ['low','medium','high'].includes(t?.priority) ? t.priority : 'medium',
                                            weight: Math.min(5, Math.max(1, parseInt(t?.weight ?? 2))),
                                            checklist: Array.isArray(t?.checklist) && t.checklist.length ? t.checklist : [''],
                                        }));
                                        if (!this.tasks.length) this.addTask();
                                    },
                                    addTask() {
                                        this.tasks.push({ title: '', description: '', type: 'feature', priority: 'medium', weight: 2, checklist: [''] });
                                    },
                                    removeTask(index) {
                                        this.tasks.splice(index, 1);
                                        if (!this.tasks.length) this.addTask();
                                    },
                                    addChecklist(taskIndex) {
                                        if (!Array.isArray(this.tasks[taskIndex].checklist)) this.tasks[taskIndex].checklist = [];
                                        this.tasks[taskIndex].checklist.push('');
                                    },
                                    removeChecklist(taskIndex, itemIndex) {
                                        const list = this.tasks[taskIndex].checklist;
                                        list.splice(itemIndex, 1);
                                        if (!list.length) list.push('');
                                    },
                                    payload() {
                                        return {
                                            target_mode: this.targetMode,
                                            sprint_name: this.sprintName,
                                            sprint_description: this.sprintDescription,
                                            existing_sprint_id: this.targetMode === 'existing' ? this.existingSprintId : '',
                                            tasks: this.tasks.map(task => ({
                                                title: task.title,
                                                description: task.description,
                                                type: task.type,
                                                priority: task.priority,
                                                weight: task.weight,
                                                checklist: (Array.isArray(task.checklist) ? task.checklist : []).filter(i => String(i).trim() !== ''),
                                            })),
                                        };
                                    },
                                    init() {
                                        this.normalizeTasks();
                                    }
                                 }">
                                <div x-show="!skipped" x-cloak class="bg-white border border-line rounded-lg shadow-sm overflow-hidden">
                                    <div x-show="confirmed" class="flex items-center gap-2 px-3 py-2 bg-[#f0faf5] border-l-[3px] border-[#2e7d55]">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-[#2e7d55] shrink-0">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        <span class="text-[12px] font-medium text-[#2e7d55]">Sprint batch created</span>
                                    </div>

                                    <div x-show="!confirmed" class="p-3 space-y-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-[12px] font-semibold text-ink">Sprint + Tasks Proposal</p>
                                            <button x-on:click="skipped = true; $wire.skipSprintWithTasks({{ $msgDbId }})"
                                                    class="text-[10px] text-muted hover:text-ink cursor-pointer">Skip</button>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-muted">Target sprint</label>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <button type="button"
                                                        x-on:click="targetMode = 'new'"
                                                        x-bind:class="targetMode === 'new' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                                                        class="px-2 py-1 text-[11px] font-medium rounded-md border transition-colors cursor-pointer">Create as new sprint</button>
                                                <button type="button"
                                                        x-on:click="targetMode = 'existing'"
                                                        x-bind:class="targetMode === 'existing' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                                                        class="px-2 py-1 text-[11px] font-medium rounded-md border transition-colors cursor-pointer">Add to existing sprint</button>
                                            </div>
                                            <div x-show="targetMode === 'existing'" x-cloak>
                                                <select x-model="existingSprintId" class="w-full text-[12px] text-ink bg-surface border border-line rounded-md px-2 py-1.5 outline-none focus:border-accent cursor-pointer">
                                                    <option value="">Select sprint...</option>
                                                    @foreach($availableSprints as $sp)
                                                    <option value="{{ $sp['id'] }}">{{ $sp['name'] }} ({{ ucfirst($sp['status']) }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div x-show="targetMode === 'new'" x-cloak class="space-y-2">
                                            <input x-model="sprintName" type="text" placeholder="Sprint name"
                                                   class="w-full text-[12px] text-ink bg-surface border border-line rounded-md px-2 py-1.5 outline-none focus:border-accent">
                                            <textarea x-model="sprintDescription" rows="2" placeholder="Sprint description"
                                                      class="w-full text-[12px] text-dim bg-surface border border-line rounded-md px-2 py-1.5 outline-none focus:border-accent resize-none"></textarea>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(task, tIndex) in tasks" :key="`task-${tIndex}`">
                                                <div class="border border-hairline rounded-md p-2.5 bg-white space-y-2">
                                                    <div class="flex items-center gap-2">
                                                        <input x-model="task.title" type="text" placeholder="Task title"
                                                               class="flex-1 text-[12px] font-medium text-ink bg-surface border border-line rounded-md px-2 py-1.5 outline-none focus:border-accent">
                                                        <button x-on:click="removeTask(tIndex)" class="text-[10px] text-muted hover:text-danger cursor-pointer">Remove task</button>
                                                    </div>

                                                    <textarea x-model="task.description" rows="2" placeholder="Task description"
                                                              class="w-full text-[11px] text-dim bg-surface border border-line rounded-md px-2 py-1.5 outline-none focus:border-accent resize-none"></textarea>

                                                    <div class="grid grid-cols-3 gap-1.5">
                                                        <select x-model="task.type" class="text-[11px] text-dim bg-surface border border-line rounded px-1.5 py-1 outline-none cursor-pointer">
                                                            <option value="feature">feature</option>
                                                            <option value="bug">bug</option>
                                                            <option value="change">change</option>
                                                        </select>
                                                        <select x-model="task.priority" class="text-[11px] text-dim bg-surface border border-line rounded px-1.5 py-1 outline-none cursor-pointer">
                                                            <option value="low">low</option>
                                                            <option value="medium">medium</option>
                                                            <option value="high">high</option>
                                                        </select>
                                                        <input x-model.number="task.weight" type="number" min="1" max="5" step="1"
                                                               class="text-[11px] text-dim bg-surface border border-line rounded px-1.5 py-1 outline-none" placeholder="Weight">
                                                    </div>

                                                    <div class="space-y-1.5">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Checklist</span>
                                                            <button x-on:click="addChecklist(tIndex)" class="text-[10px] text-accent hover:underline cursor-pointer">+ Add item</button>
                                                        </div>
                                                        <template x-for="(item, cIndex) in task.checklist" :key="`task-${tIndex}-check-${cIndex}`">
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-muted shrink-0"></span>
                                                                <input x-model="task.checklist[cIndex]" type="text" placeholder="Checklist item"
                                                                       class="flex-1 text-[11px] text-dim bg-surface border border-line rounded px-2 py-1 outline-none focus:border-accent">
                                                                <button x-on:click="removeChecklist(tIndex, cIndex)" class="text-[10px] text-muted hover:text-danger cursor-pointer">Remove</button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <button x-on:click="addTask()" class="px-2 py-1 rounded-md text-[11px] font-medium border border-line text-dim hover:text-ink hover:bg-surface transition-colors cursor-pointer">+ Add task</button>
                                        </div>

                                        <div class="flex justify-end">
                                            <button x-on:click="confirmed = true; $wire.confirmSprintWithTasks({{ $msgDbId }}, payload())"
                                                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover cursor-pointer transition-colors">Create sprint + tasks</button>
                                        </div>
                                    </div>
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
                                        checklist: @js(is_array($action['checklist'] ?? null) ? array_values($action['checklist']) : []),
                                        sprintId: '',
                                        confirmed: {{ ($action['confirmed'] ?? false) ? 'true' : 'false' }},
                                        skipped: {{ ($action['skipped'] ?? false) ? 'true' : 'false' }},
                                        editTitle: false,
                                        editDesc: false,
                                        addChecklistItem() {
                                            if (!Array.isArray(this.checklist)) this.checklist = [];
                                            this.checklist.push('');
                                        },
                                        removeChecklistItem(index) {
                                            this.checklist.splice(index, 1);
                                        },
                                        init() {
                                            this.$watch('editTitle', v => { if (v) this.$nextTick(() => this.$refs.titleInput && this.$refs.titleInput.focus()); });
                                            if (!Array.isArray(this.checklist)) this.checklist = [];
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

                                        @if($actType === 'tasks')
                                        <div class="mb-2 space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Checklist</span>
                                                <button x-on:click="addChecklistItem()" class="text-[10px] text-accent hover:underline cursor-pointer">+ Add item</button>
                                            </div>
                                            <template x-if="checklist.length === 0">
                                                <p class="text-[11px] text-muted italic">No checklist items.</p>
                                            </template>
                                            <template x-for="(item, cIdx) in checklist" :key="`check-${cIdx}`">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-muted shrink-0"></span>
                                                    <input x-model="checklist[cIdx]" type="text" placeholder="Checklist item"
                                                           class="flex-1 text-[11px] text-dim bg-surface border border-line rounded px-2 py-1 outline-none focus:border-accent">
                                                    <button x-on:click="removeChecklistItem(cIdx)" class="text-[10px] text-muted hover:text-danger cursor-pointer">Remove</button>
                                                </div>
                                            </template>
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
                                                x-on:click="confirmed = true; $wire.confirmAction({{ $msgDbId }}, {{ $aIdx }}, { title, description, type: itemType, weight, priority, checklist, sprintId, actionType: '{{ $actType }}', targetAction: (sprintId !== '') ? 'sprint' : 'backlog' })"
                                                class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover cursor-pointer transition-colors"
                                            >
                                                @if($actType === 'tasks')
                                                <span x-text="sprintId !== '' ? 'Create in sprint' : 'Create task'"></span>
                                                @else
                                                Add to backlog
                                                @endif
                                            </button>
                                            @if($actType === 'tasks')
                                            <button
                                                x-show="sprintId !== ''"
                                                x-on:click="confirmed = true; $wire.confirmAction({{ $msgDbId }}, {{ $aIdx }}, { title, description, type: itemType, weight, priority, checklist, sprintId: '', actionType: '{{ $actType }}', targetAction: 'backlog' })"
                                                class="px-2.5 py-1 rounded-md text-[11px] font-medium border border-line text-dim hover:text-ink hover:bg-surface cursor-pointer transition-colors"
                                            >Create without sprint</button>
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

                @php
                    $latestClarification = collect($messages)
                        ->reverse()
                        ->first(fn ($msg) => (($msg['actions']['type'] ?? null) === 'clarification') && empty($msg['actions']['answered']));
                @endphp

                @if($latestClarification && ! $isStreaming)
                @php
                    $clarAction = $latestClarification['actions'];
                    $clarQuestions = is_array($clarAction['questions'] ?? null) ? $clarAction['questions'] : [];
                    $clarMessageId = $latestClarification['id'] ?? 0;
                @endphp
                <div
                    class="absolute inset-x-0 bottom-0 z-30"
                    data-clar-questions="{!! htmlspecialchars(json_encode($clarQuestions, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') !!}"
                    data-clar-msg-id="{{ (int) $clarMessageId }}"
                    x-data="{
                        open: true,
                        current: 0,
                        questions: [],
                        msgId: 0,
                        answers: {},
                        textValue: '',
                        selectedPill: '',
                        multiSelected: [],
                        otherValue: '',
                        multiOtherValue: '',
                        get total() { return this.questions.length || 0; },
                        get currentQuestion() { return this.questions[this.current] || null; },
                        isOtherOption(opt) {
                            return /^(other|something else|something-else|else|another)$/i.test(String(opt || '').trim());
                        },
                        firstOtherOption(options) {
                            const opts = Array.isArray(options) ? options : [];
                            return opts.find((opt) => this.isOtherOption(opt)) || null;
                        },
                        focusWithin(el) {
                            this.$nextTick(() => {
                                const input = el?.querySelector('input[type=text]');
                                if (input) input.focus();
                            });
                        },
                        syncCurrentFromAnswers() {
                            const q = this.currentQuestion;
                            if (!q) return;
                            const existing = this.answers[q.id];
                            this.textValue = '';
                            this.selectedPill = '';
                            this.multiSelected = [];
                            this.otherValue = '';
                            this.multiOtherValue = '';
                            if (q.type === 'multiselect' && Array.isArray(existing)) {
                                const opts = Array.isArray(q.options) ? q.options : [];
                                const otherOpt = this.firstOtherOption(opts);
                                const known = existing.filter((val) => opts.includes(val));
                                const custom = existing.find((val) => !opts.includes(val));
                                this.multiSelected = [...known];
                                if (otherOpt && typeof custom === 'string' && custom.trim() !== '') {
                                    this.multiSelected.push(otherOpt);
                                    this.multiOtherValue = custom;
                                }
                            } else if (q.type === 'pills' && typeof existing === 'string') {
                                const opts = Array.isArray(q.options) ? q.options : [];
                                const otherOpt = this.firstOtherOption(opts);
                                if (opts.includes(existing)) {
                                    this.selectedPill = existing;
                                } else if (otherOpt) {
                                    this.selectedPill = otherOpt;
                                    this.otherValue = existing;
                                } else {
                                    this.otherValue = existing;
                                }
                            } else if (q.type === 'text' && typeof existing === 'string') {
                                this.textValue = existing;
                            }
                        },
                        saveCurrentAnswerAndContinue() {
                            const q = this.currentQuestion;
                            if (!q) return;
                            if (q.type === 'text') {
                                const val = this.textValue.trim();
                                if (q.required && val === '') return;
                                this.answers[q.id] = val;
                            } else if (q.type === 'multiselect') {
                                const options = Array.isArray(q.options) ? q.options : [];
                                const normalized = [];
                                for (const opt of this.multiSelected) {
                                    if (this.isOtherOption(opt)) {
                                        const custom = this.multiOtherValue.trim();
                                        if (custom !== '') normalized.push(custom);
                                    } else if (options.includes(opt)) {
                                        normalized.push(opt);
                                    }
                                }
                                if (q.required && normalized.length === 0) return;
                                this.answers[q.id] = normalized;
                            } else {
                                let val = this.selectedPill;
                                if (this.isOtherOption(this.selectedPill)) {
                                    val = this.otherValue.trim();
                                }
                                if (q.required && val === '') return;
                                this.answers[q.id] = val;
                            }

                            if (this.current < this.total - 1) {
                                this.current += 1;
                                this.syncCurrentFromAnswers();
                            } else {
                                this.open = false;
                                $wire.submitClarificationAnswers(this.msgId, this.answers);
                            }
                        },
                        skipCurrent() {
                            const q = this.currentQuestion;
                            if (!q || q.required) return;
                            this.answers[q.id] = '';
                            if (this.current < this.total - 1) {
                                this.current += 1;
                                this.syncCurrentFromAnswers();
                            } else {
                                this.open = false;
                                $wire.submitClarificationAnswers(this.msgId, this.answers);
                            }
                        },
                        back() {
                            if (this.current === 0) return;
                            this.current -= 1;
                            this.syncCurrentFromAnswers();
                        },
                        choosePill(opt, event) {
                            this.selectedPill = opt;
                            if (this.isOtherOption(opt)) {
                                this.focusWithin(event?.currentTarget?.closest('[data-option-row]'));
                                return;
                            }
                            this.otherValue = '';
                            this.answers[this.currentQuestion.id] = opt;
                            this.saveCurrentAnswerAndContinue();
                        },
                        toggleMultiOption(opt, checked, event) {
                            if (checked) {
                                if (!this.multiSelected.includes(opt)) this.multiSelected.push(opt);
                                if (this.isOtherOption(opt)) {
                                    this.focusWithin(event?.currentTarget?.closest('[data-option-row]'));
                                }
                                return;
                            }

                            this.multiSelected = this.multiSelected.filter((v) => v !== opt);
                            if (this.isOtherOption(opt)) {
                                this.multiOtherValue = '';
                            }
                        },
                        init() {
                            try {
                                this.questions = JSON.parse(this.$el.getAttribute('data-clar-questions') || '[]');
                            } catch (_) { this.questions = []; }
                            this.msgId = parseInt(this.$el.getAttribute('data-clar-msg-id') || '0', 10);
                            this.syncCurrentFromAnswers();
                        }
                    }"
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-4"
                >
                    <div class="h-[60%] bg-white border-t border-line rounded-t-2xl shadow-modal flex flex-col">
                        <div class="flex justify-center pt-2 pb-1"><span class="w-10 h-1 rounded-full bg-hairline"></span></div>
                        <div class="px-4 pb-2 border-b border-hairline">
                            <p class="text-[16px] font-semibold text-ink">A few quick questions</p>
                            <p class="text-[11px] text-muted mt-0.5" x-text="`Question ${current + 1} of ${total}`"></p>
                        </div>

                        <div class="flex-1 overflow-y-auto px-4 py-4">
                            <template x-if="currentQuestion">
                                <div class="space-y-3">
                                    <p class="text-[15px] font-medium text-ink leading-relaxed" x-text="currentQuestion.text"></p>

                                    <template x-if="currentQuestion.type === 'pills'">
                                        <div class="space-y-2">
                                            <div class="space-y-1.5">
                                                <template x-for="opt in (currentQuestion.options || [])" :key="opt">
                                                    <div data-option-row class="space-y-1">
                                                        <button
                                                            type="button"
                                                            x-on:click="choosePill(opt, $event)"
                                                            x-bind:class="selectedPill === opt ? 'border-accent text-accent bg-accent-light' : 'border-line text-dim bg-surface hover:border-accent hover:text-accent'"
                                                            class="w-full text-left px-2.5 py-1.5 rounded-md text-[12px] font-medium border transition-colors cursor-pointer"
                                                            x-text="opt"
                                                        ></button>
                                                        <input
                                                            x-show="isOtherOption(opt) && selectedPill === opt"
                                                            x-cloak
                                                            x-model="otherValue"
                                                            type="text"
                                                            placeholder="Type your answer..."
                                                            class="w-full text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent"
                                                        >
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="currentQuestion.type === 'text'">
                                        <div class="space-y-2">
                                            <textarea x-model="textValue" rows="4" placeholder="Type your answer..."
                                                      class="w-full text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-2 outline-none focus:border-accent resize-none"></textarea>
                                        </div>
                                    </template>

                                    <template x-if="currentQuestion.type === 'multiselect'">
                                        <div class="space-y-2">
                                            <div class="space-y-1.5">
                                                <template x-for="opt in (currentQuestion.options || [])" :key="opt">
                                                    <div data-option-row class="space-y-1">
                                                        <label class="flex items-center gap-2 text-[13px] text-dim cursor-pointer">
                                                            <input
                                                                type="checkbox"
                                                                :checked="multiSelected.includes(opt)"
                                                                x-on:change="toggleMultiOption(opt, $event.target.checked, $event)"
                                                                class="rounded accent-accent"
                                                            >
                                                            <span x-text="opt"></span>
                                                        </label>
                                                        <input
                                                            x-show="isOtherOption(opt) && multiSelected.includes(opt)"
                                                            x-cloak
                                                            x-model="multiOtherValue"
                                                            type="text"
                                                            placeholder="Type your answer..."
                                                            class="w-full text-[13px] text-ink bg-surface border border-line rounded-md px-2.5 py-1.5 outline-none focus:border-accent"
                                                        >
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div class="px-4 py-3 border-t border-hairline bg-white flex items-center justify-between">
                            <div class="text-[11px] text-muted" x-text="`Question ${current + 1} of ${total}`"></div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    x-show="current > 0"
                                    x-cloak
                                    x-on:click="back()"
                                    class="px-2.5 py-1 rounded-md text-[11px] font-medium border border-line text-dim hover:text-ink hover:bg-surface transition-colors cursor-pointer"
                                >Back</button>
                                <button
                                    type="button"
                                    x-on:click="saveCurrentAnswerAndContinue()"
                                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer"
                                    x-text="current === total - 1 ? 'Submit' : 'Next'"
                                ></button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endif

            {{-- ── Streaming bubble ────────────────────────────────────────── --}}
            <div x-show="isStreaming" style="display: none" class="w-full mt-3 px-2 py-1 text-ink">
                <div class="w-full">
                    <div x-show="streamingText === ''" class="flex items-center gap-1 py-0.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-muted" style="animation: bounce 1s infinite 0ms"></span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-muted" style="animation: bounce 1s infinite 150ms"></span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-muted" style="animation: bounce 1s infinite 300ms"></span>
                    </div>
                    <div x-show="streamingText !== ''" x-html="renderStreamingMarkdown(streamingText)" class="text-[13px] leading-relaxed"></div>
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
                @php $hasPills = ! empty($selectedDocumentIds) || ! empty($selectedSprintIds) || ! empty($selectedTaskIds) || ! empty($selectedBacklogIds) || $additionalContext !== ''; @endphp
                @if($hasPills)
                <div class="flex flex-wrap gap-1 mb-1.5 px-1">
                    {{-- Document pills (indigo) --}}
                    @foreach($projectDocuments as $doc)
                        @if(in_array($doc['id'], $selectedDocumentIds))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                            {{ \Illuminate\Support\Str::limit($doc['title'], 20) }}
                            <button
                                wire:click="removeDocumentContext({{ $doc['id'] }})"
                                class="cursor-pointer hover:opacity-70 transition-opacity leading-none"
                            >&times;</button>
                        </span>
                        @endif
                    @endforeach
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
                        x-ref="chatInput"
                        rows="2"
                        placeholder="{{ $projectId ? 'Ask about this project…' : 'Select a project first…' }}"
                        class="flex-1 text-[13px] text-ink bg-transparent outline-none resize-none placeholder:text-muted placeholder:italic px-1"
                        style="max-height: 120px; line-height: 1.5"
                        x-on:keydown.enter="if (!$event.shiftKey && !isStreaming) { $event.preventDefault(); $wire.sendMessage().then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
                        x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                        x-bind:disabled="!projectIdState || isStreaming"
                    ></textarea>

                    <button
                        x-show="isStreaming"
                        x-on:click="stopStreaming()"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg shrink-0 transition-colors duration-150 bg-[#fff0f0] hover:bg-[#ffe0e0] text-danger border border-[#ffd0d0] cursor-pointer"
                        title="Stop"
                    >
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="6" y="6" width="12" height="12" rx="1.5"></rect>
                        </svg>
                    </button>

                    <button
                        x-show="!isStreaming"
                        x-on:click="if (!isStreaming && projectIdState && inputText.trim().length > 0) { $wire.sendMessage().then(() => $nextTick(() => $refs.chatInput && $refs.chatInput.focus())); }"
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
