<div
    x-data="{
        open: @entangle('isOpen').live,
        selectedProjectId: @entangle('selectedProjectId').live,
        captureText: @entangle('captureText'),
        captureType: @entangle('captureType').live,
        toastMessage: '',
        touchStartY: null,
        touchCurrentY: null,
        isDesktop() { return window.matchMedia('(min-width: 640px)').matches; },
        closePanel() { $wire.close(); },
        focusTextarea() {
            this.$nextTick(() => {
                if (this.$refs.captureInput) {
                    this.$refs.captureInput.focus();
                }
            });
        },
        startTouch(e) {
            this.touchStartY = e.changedTouches[0].clientY;
            this.touchCurrentY = this.touchStartY;
        },
        moveTouch(e) {
            this.touchCurrentY = e.changedTouches[0].clientY;
        },
        endTouch() {
            if (this.touchStartY === null || this.touchCurrentY === null) return;
            if ((this.touchCurrentY - this.touchStartY) > 80) {
                this.closePanel();
            }
            this.touchStartY = null;
            this.touchCurrentY = null;
        },
    }"
    x-on:open-quick-capture.window="$wire.open($event.detail?.projectId ?? null, $event.detail?.text ?? '')"
    x-on:quick-capture-toast.window="toastMessage = $event.detail?.message ?? ''; setTimeout(() => toastMessage = '', 1800)"
    x-on:keydown.escape.window="if (open) closePanel()"
    x-effect="if (open) focusTextarea()"
>
    {{-- Mobile floating action button --}}
    <button
        x-on:click="$wire.open()"
        class="sm:hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-14 h-14 rounded-full bg-accent hover:bg-accent-hover text-white shadow-[0_4px_14px_rgba(20,20,19,0.18)] transition-colors duration-150 cursor-pointer inline-flex items-center justify-center"
        aria-label="Open quick capture"
        title="Quick capture"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
        </svg>
    </button>

    {{-- Shared backdrop --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="closePanel()"
        class="fixed inset-0 z-60 bg-black/40"
    ></div>

    {{-- Mobile bottom sheet --}}
    <div
        x-show="open && !isDesktop()"
        x-cloak
        x-transition:enter="transition duration-220 ease-out"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-180 ease-in"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        x-on:click.stop
        x-on:touchstart="startTouch($event)"
        x-on:touchmove="moveTouch($event)"
        x-on:touchend="endTouch()"
        class="sm:hidden fixed inset-x-0 bottom-0 z-70 h-[75vh] bg-white rounded-t-2xl border-t border-line shadow-[0_-8px_24px_rgba(20,20,19,0.12)] flex flex-col"
    >
        <div class="flex justify-center pt-2 pb-1">
            <span class="w-10 h-1 rounded-full bg-hairline"></span>
        </div>

        <div class="px-4 pb-3 border-b border-hairline">
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Project</p>
            <div class="flex items-center gap-2 overflow-x-auto pb-0.5">
                @foreach($projects as $project)
                <button
                    type="button"
                    x-on:click="selectedProjectId = {{ $project['id'] }}; $wire.selectProject({{ $project['id'] }}); localStorage.setItem('recent_project_id', '{{ $project['id'] }}')"
                    x-bind:class="selectedProjectId == {{ $project['id'] }} ? 'bg-accent-light text-accent border-accent-light' : 'bg-surface text-dim border-line'"
                    class="shrink-0 px-3 py-1.5 rounded-full text-[12px] font-medium border transition-colors cursor-pointer"
                >{{ $project['name'] }}</button>
                @endforeach
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            <textarea
                x-ref="captureInput"
                x-model="captureText"
                rows="7"
                placeholder="What needs to be done?"
                class="w-full min-h-40 text-[14px] text-ink bg-surface border border-line rounded-xl px-3 py-3 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted placeholder:italic"
            ></textarea>

            <div class="mt-3 flex items-center gap-2">
                <button type="button" x-on:click="captureType='bug'; $wire.setType('bug')"
                        x-bind:class="captureType === 'bug' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                        class="px-3 py-1.5 rounded-lg text-[12px] font-medium border transition-colors cursor-pointer">Bug</button>
                <button type="button" x-on:click="captureType='feature'; $wire.setType('feature')"
                        x-bind:class="captureType === 'feature' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                        class="px-3 py-1.5 rounded-lg text-[12px] font-medium border transition-colors cursor-pointer">Feature</button>
                <button type="button" x-on:click="captureType='task'; $wire.setType('task')"
                        x-bind:class="captureType === 'task' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                        class="px-3 py-1.5 rounded-lg text-[12px] font-medium border transition-colors cursor-pointer">Task</button>
            </div>
        </div>

        <div class="px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] border-t border-hairline bg-white">
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    x-on:click="$wire.saveAsTask()"
                    x-bind:disabled="captureText.trim() === '' || !selectedProjectId"
                    class="min-h-11 px-3 py-2 rounded-lg text-[13px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >Save as task</button>
                <button
                    type="button"
                    x-on:click="$wire.discussWithAi()"
                    x-bind:disabled="captureText.trim() === '' || !selectedProjectId"
                    class="min-h-11 px-3 py-2 rounded-lg text-[13px] font-medium bg-surface border border-line text-ink hover:bg-hairline transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >Discuss with AI</button>
            </div>
        </div>
    </div>

    {{-- Desktop modal --}}
    <div
        x-show="open && isDesktop()"
        x-cloak
        x-transition:enter="transition duration-220 ease-out"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition duration-180 ease-in"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-on:click.stop
        class="hidden sm:flex fixed inset-0 z-70 items-center justify-center p-4"
    >
        <div class="w-full max-w-lg bg-white border border-line rounded-2xl shadow-modal overflow-hidden">
            <div class="px-5 py-4 border-b border-hairline flex items-center justify-between">
                <p class="text-[16px] font-semibold text-ink">Quick Capture</p>
                <button type="button" x-on:click="closePanel()"
                        class="w-7 h-7 rounded-full bg-surface text-muted hover:text-ink transition-colors cursor-pointer inline-flex items-center justify-center">×</button>
            </div>

            <div class="px-5 py-4 space-y-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Project</p>
                    <div class="flex items-center gap-2 overflow-x-auto pb-0.5">
                        @foreach($projects as $project)
                        <button
                            type="button"
                            x-on:click="selectedProjectId = {{ $project['id'] }}; $wire.selectProject({{ $project['id'] }}); localStorage.setItem('recent_project_id', '{{ $project['id'] }}')"
                            x-bind:class="selectedProjectId == {{ $project['id'] }} ? 'bg-accent-light text-accent border-accent-light' : 'bg-surface text-dim border-line'"
                            class="shrink-0 px-3 py-1.5 rounded-full text-[12px] font-medium border transition-colors cursor-pointer"
                        >{{ $project['name'] }}</button>
                        @endforeach
                    </div>
                </div>

                <textarea
                    x-ref="captureInput"
                    x-model="captureText"
                    rows="6"
                    placeholder="What needs to be done?"
                    class="w-full text-[14px] text-ink bg-surface border border-line rounded-xl px-3 py-3 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted placeholder:italic"
                ></textarea>

                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="captureType='bug'; $wire.setType('bug')"
                            x-bind:class="captureType === 'bug' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                            class="px-3 py-1.5 rounded-lg text-[12px] font-medium border transition-colors cursor-pointer">Bug</button>
                    <button type="button" x-on:click="captureType='feature'; $wire.setType('feature')"
                            x-bind:class="captureType === 'feature' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                            class="px-3 py-1.5 rounded-lg text-[12px] font-medium border transition-colors cursor-pointer">Feature</button>
                    <button type="button" x-on:click="captureType='task'; $wire.setType('task')"
                            x-bind:class="captureType === 'task' ? 'bg-accent text-white border-accent' : 'bg-surface text-dim border-line'"
                            class="px-3 py-1.5 rounded-lg text-[12px] font-medium border transition-colors cursor-pointer">Task</button>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-hairline bg-white">
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        x-on:click="$wire.saveAsTask()"
                        x-bind:disabled="captureText.trim() === '' || !selectedProjectId"
                        class="min-h-11 px-3 py-2 rounded-lg text-[13px] font-medium bg-accent text-white hover:bg-accent-hover transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >Save as task</button>
                    <button
                        type="button"
                        x-on:click="$wire.discussWithAi()"
                        x-bind:disabled="captureText.trim() === '' || !selectedProjectId"
                        class="min-h-11 px-3 py-2 rounded-lg text-[13px] font-medium bg-surface border border-line text-ink hover:bg-hairline transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >Discuss with AI</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div
        x-show="toastMessage !== ''"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-120"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-24 sm:bottom-6 left-1/2 -translate-x-1/2 z-80 px-3 py-2 rounded-lg bg-ink text-white text-[12px] font-medium shadow-dropdown"
        x-text="toastMessage"
    ></div>
</div>
