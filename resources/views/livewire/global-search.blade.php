<div
    x-data="{
        activeIndex: 0,
        actionButtons() {
            if (!this.$refs.actions) return [];
            return Array.from(this.$refs.actions.querySelectorAll('[data-action-item]'));
        },
        move(step) {
            const items = this.actionButtons();
            if (!items.length) return;
            this.activeIndex = (this.activeIndex + step + items.length) % items.length;
            items[this.activeIndex].focus();
        },
        selectActive() {
            const items = this.actionButtons();
            if (!items.length) return;
            items[this.activeIndex]?.click();
        },
        syncIndex() {
            const items = this.actionButtons();
            if (!items.length) { this.activeIndex = 0; return; }
            if (this.activeIndex >= items.length) this.activeIndex = items.length - 1;
        }
    }"
    x-on:open-global-search.window="$wire.open()"
>
    @if($isOpen)
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/60 z-40" wire:click="close"></div>

        {{-- Modal panel --}}
        <div
            class="fixed left-1/2 top-20 -translate-x-1/2 w-full max-w-2xl z-50 px-4"
            x-init="$nextTick(() => { activeIndex = 0; syncIndex(); })"
            x-on:keydown.escape.window.prevent.stop="$wire.close()"
            x-on:keydown.arrow-down.window.prevent="move(1)"
            x-on:keydown.arrow-up.window.prevent="move(-1)"
            x-on:keydown.enter.window.prevent="selectActive()"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/10 border border-gray-100 overflow-hidden"
                wire:click.stop
            >
                <div class="px-4 py-3 flex items-center gap-3 border-b border-gray-100">
                    {{-- Search icon (hidden while searching) --}}
                    <svg wire:loading.remove wire:target="updatedQuery" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                    {{-- Spinner shown while searching --}}
                    <svg wire:loading wire:target="updatedQuery" class="w-4 h-4 text-gray-300 animate-spin flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <input
                        autofocus
                        type="text"
                        wire:model.live.debounce.300ms="query"
                        placeholder="Search or jump to..."
                        class="w-full border-0 p-0 bg-transparent outline-none focus:outline-none focus:ring-0 text-base text-gray-800 placeholder-gray-400"
                    >
                    <span class="ml-auto text-xs bg-gray-100 px-2 py-1 rounded font-mono text-gray-500">ESC</span>
                </div>

                <div class="max-h-[60vh] overflow-y-auto">
                    @if(trim($query) === '')
                    {{-- ── Empty query: recents + quick actions ────────────────── --}}
                    <div class="px-3 py-2" x-ref="actions">
                        @php $actionIndex = 0; @endphp

                        {{-- Recent searches --}}
                        @if(count($recentSearches) > 0)
                        <div class="mb-3">
                            <div class="flex items-center justify-between px-2 mb-1">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Recent</span>
                                <button
                                    type="button"
                                    wire:click="clearRecentSearches"
                                    class="text-[11px] text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
                                >Clear</button>
                            </div>
                            <div class="space-y-0.5">
                                @foreach($recentSearches as $recent)
                                <button
                                    type="button"
                                    data-action-item
                                    wire:click="searchRecent('{{ addslashes($recent) }}')"
                                    x-on:mouseenter="activeIndex = {{ $actionIndex }}"
                                    :class="activeIndex === {{ $actionIndex }} ? 'bg-gray-100' : ''"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer hover:bg-gray-50 text-sm text-gray-600 transition-colors group"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                    <span class="flex-1 text-left">{{ $recent }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-300 group-hover:text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 7-7 7 7M5 19l7-7 7 7"/>
                                    </svg>
                                </button>
                                @php $actionIndex++; @endphp
                                @endforeach
                            </div>
                            <div class="mt-2 border-t border-gray-100"></div>
                        </div>
                        @endif

                        {{-- Quick Actions --}}
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1">Quick Actions</p>

                        <div class="space-y-1">
                            <button
                                type="button"
                                data-action-item
                                wire:click="goToNewProject"
                                x-on:mouseenter="activeIndex = {{ $actionIndex }}"
                                x-on:focus="activeIndex = {{ $actionIndex }}"
                                :class="activeIndex === {{ $actionIndex }} ? 'bg-gray-100' : ''"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-gray-50 text-sm text-gray-700 transition-colors"
                            >
                                <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500" aria-hidden="true">📁</span>
                                <span class="flex-1 text-left">New Project</span>
                                <span class="text-[10px] text-gray-300 font-mono">⌘⇧P</span>
                            </button>
                            @php $actionIndex++; @endphp

                            <button
                                type="button"
                                data-action-item
                                wire:click="openQuickCustomerModal"
                                x-on:mouseenter="activeIndex = {{ $actionIndex }}"
                                x-on:focus="activeIndex = {{ $actionIndex }}"
                                :class="activeIndex === {{ $actionIndex }} ? 'bg-gray-100' : ''"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-gray-50 text-sm text-gray-700 transition-colors"
                            >
                                <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500" aria-hidden="true">👤</span>
                                <span class="flex-1 text-left">New Customer</span>
                                <span class="text-[10px] text-gray-300 font-mono">⌘⇧C</span>
                            </button>
                            @php $actionIndex++; @endphp

                            <button
                                type="button"
                                data-action-item
                                wire:click="goToNewInvoice"
                                x-on:mouseenter="activeIndex = {{ $actionIndex }}"
                                x-on:focus="activeIndex = {{ $actionIndex }}"
                                :class="activeIndex === {{ $actionIndex }} ? 'bg-gray-100' : ''"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-gray-50 text-sm text-gray-700 transition-colors"
                            >
                                <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500" aria-hidden="true">🧾</span>
                                <span class="flex-1 text-left">New Invoice</span>
                                <span class="text-[10px] text-gray-300 font-mono">⌘⇧I</span>
                            </button>
                            @php $actionIndex++; @endphp
                        </div>

                        {{-- Navigation shortcuts section --}}
                        <div class="mt-3 pt-3 border-t border-gray-100" x-data="{ navOpen: false }">
                            <button
                                type="button"
                                @click="navOpen = !navOpen"
                                class="w-full flex items-center gap-2 px-2 mb-1 group cursor-pointer"
                            >
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 flex-1 text-left">Jump to...</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-300 transition-transform" :class="navOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="navOpen" x-cloak class="space-y-0.5">
                                @php
                                    $navItems = [
                                        ['label' => 'Dashboard',  'href' => '/dashboard',        'key' => '⌘⇧D'],
                                        ['label' => 'Board',      'href' => '/board',             'key' => '⌘⇧B'],
                                        ['label' => 'Tasks',      'href' => '/tasks',             'key' => '⌘⇧T'],
                                        ['label' => 'Projects',   'href' => '/projects',          'key' => '⌘⇧P'],
                                        ['label' => 'Invoices',   'href' => '/invoices',          'key' => '⌘⇧I'],
                                        ['label' => 'Expenses',   'href' => '/expenses',          'key' => '⌘⇧E'],
                                        ['label' => 'Customers',  'href' => '/customers',         'key' => '⌘⇧C'],
                                    ];
                                @endphp
                                @foreach($navItems as $nav)
                                <a
                                    href="{{ $nav['href'] }}"
                                    wire:navigate
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm text-gray-600"
                                >
                                    <span class="flex-1 text-left">{{ $nav['label'] }}</span>
                                    <span class="text-[10px] text-gray-300 font-mono">{{ $nav['key'] }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>

                        @if($currentProjectId)
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-accent"></span>
                                    <span>In {{ $currentProjectName }}</span>
                                </p>

                                <div class="space-y-1">
                                    <button
                                        type="button"
                                        data-action-item
                                        wire:click="openNewTask"
                                        x-on:mouseenter="activeIndex = {{ $actionIndex }}"
                                        x-on:focus="activeIndex = {{ $actionIndex }}"
                                        :class="activeIndex === {{ $actionIndex }} ? 'bg-gray-100' : ''"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-gray-50 text-sm text-gray-700 transition-colors"
                                    >
                                        <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500" aria-hidden="true">✓</span>
                                        <span class="flex-1 text-left">New Task</span>
                                        <span class="text-[10px] text-gray-300 font-mono">⌘T</span>
                                    </button>
                                    @php $actionIndex++; @endphp

                                    <button
                                        type="button"
                                        data-action-item
                                        wire:click="openNewSprint"
                                        x-on:mouseenter="activeIndex = {{ $actionIndex }}"
                                        x-on:focus="activeIndex = {{ $actionIndex }}"
                                        :class="activeIndex === {{ $actionIndex }} ? 'bg-gray-100' : ''"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-gray-50 text-sm text-gray-700 transition-colors"
                                    >
                                        <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500" aria-hidden="true">🔁</span>
                                        <span class="flex-1 text-left">New Sprint</span>
                                    </button>
                                    @php $actionIndex++; @endphp

                                    <button
                                        type="button"
                                        data-action-item
                                        wire:click="openNewBacklogItem"
                                        x-on:mouseenter="activeIndex = {{ $actionIndex }}"
                                        x-on:focus="activeIndex = {{ $actionIndex }}"
                                        :class="activeIndex === {{ $actionIndex }} ? 'bg-gray-100' : ''"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-gray-50 text-sm text-gray-700 transition-colors"
                                    >
                                        <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500" aria-hidden="true">📋</span>
                                        <span class="flex-1 text-left">New Backlog Item</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    @else
                    {{-- ── Search results ─────────────────────────────────────── --}}
                    @php
                        $categories = [
                            'projects'  => ['label' => 'Projects',  'iconBg' => 'bg-blue-50',   'iconColor' => 'text-blue-500'],
                            'tasks'     => ['label' => 'Tasks',     'iconBg' => 'bg-violet-50', 'iconColor' => 'text-violet-500'],
                            'customers' => ['label' => 'Customers', 'iconBg' => 'bg-emerald-50','iconColor' => 'text-emerald-500'],
                            'invoices'  => ['label' => 'Invoices',  'iconBg' => 'bg-amber-50',  'iconColor' => 'text-amber-500'],
                            'sprints'   => ['label' => 'Sprints',   'iconBg' => 'bg-sky-50',    'iconColor' => 'text-sky-500'],
                        ];
                        $statusDot = [
                            'open'        => 'bg-gray-400',
                            'todo'        => 'bg-gray-400',
                            'in-progress' => 'bg-blue-500',
                            'review'      => 'bg-amber-500',
                            'done'        => 'bg-emerald-500',
                            'blocked'     => 'bg-red-500',
                        ];
                        $hasResults = collect($results ?? [])->some(fn($cat) => count($cat) > 0);
                        $flatIdx = 0;
                    @endphp

                    @if(! $hasResults)
                        {{-- No results --}}
                        <div class="px-4 py-8 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                            </svg>
                            <p class="text-sm text-gray-400 mb-4">No results for "<span class="font-medium text-gray-600">{{ $query }}</span>"</p>
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                @if($currentProjectId)
                                <button
                                    type="button"
                                    wire:click="openNewBacklogItem"
                                    class="inline-flex items-center gap-1.5 text-[12px] text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-full transition-colors cursor-pointer"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    Create "{{ Str::limit($query, 20) }}" as backlog item
                                </button>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="py-1" x-ref="actions">
                            @foreach($categories as $key => $meta)
                                @if(!empty($results[$key]))
                                    {{-- Category header --}}
                                    <div class="flex items-center gap-2 px-4 pt-3 pb-1">
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ $meta['label'] }}</span>
                                        <span class="text-[10px] text-gray-400 bg-gray-100 rounded-full px-1.5 py-px leading-none">{{ count($results[$key]) }}</span>
                                    </div>

                                    @foreach($results[$key] as $item)
                                        @php
                                            $dot = $statusDot[$item['status'] ?? ''] ?? 'bg-gray-300';
                                        @endphp
                                        <a
                                            href="{{ $item['url'] }}"
                                            wire:navigate
                                            data-action-item
                                            x-on:mouseenter="activeIndex = {{ $flatIdx }}"
                                            :class="activeIndex === {{ $flatIdx }} ? 'bg-gray-50' : ''"
                                            class="flex items-center gap-3 px-3 py-2.5 mx-1 rounded-lg hover:bg-gray-50 transition-colors"
                                        >
                                            {{-- Category icon --}}
                                            <span class="w-8 h-8 rounded-lg {{ $meta['iconBg'] }} flex-shrink-0 flex items-center justify-center {{ $meta['iconColor'] }}">
                                                @if($key === 'projects')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                                                @elseif($key === 'tasks')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @elseif($key === 'customers')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                                @elseif($key === 'invoices')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                                @elseif($key === 'sprints')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                                @endif
                                            </span>

                                            {{-- Title + subtitle --}}
                                            <span class="flex-1 min-w-0">
                                                <span class="flex items-center gap-1.5">
                                                    @if($key === 'projects' && !empty($item['color']))
                                                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $item['color'] }}"></span>
                                                    @elseif($key === 'tasks' && !empty($item['status']))
                                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $statusDot[$item['status']] ?? 'bg-gray-300' }}"></span>
                                                    @elseif($key === 'customers')
                                                        <span class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-semibold text-gray-500 flex-shrink-0">{{ strtoupper(substr($item['title'], 0, 1)) }}</span>
                                                    @endif
                                                    <span class="text-sm font-medium text-gray-800 truncate">{{ $item['title'] }}</span>
                                                </span>
                                                <span class="block text-[11px] text-gray-400 truncate mt-px">{{ $item['subtitle'] }}</span>
                                            </span>

                                            {{-- Right badge --}}
                                            @if($key === 'tasks' && !empty($item['type']))
                                                @php
                                                    $typeBadge = match($item['type']) {
                                                        'bug'         => 'bg-red-100 text-red-600',
                                                        'feature'     => 'bg-blue-100 text-blue-600',
                                                        'improvement' => 'bg-purple-100 text-purple-600',
                                                        default       => 'bg-gray-100 text-gray-500',
                                                    };
                                                @endphp
                                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded {{ $typeBadge }} flex-shrink-0">{{ $item['type'] }}</span>
                                            @elseif($key === 'invoices' && !empty($item['payment_status']))
                                                @php
                                                    $statusBadge = match($item['payment_status']) {
                                                        'paid'    => 'bg-emerald-100 text-emerald-700',
                                                        'overdue' => 'bg-red-100 text-red-600',
                                                        'partial' => 'bg-blue-100 text-blue-600',
                                                        default   => 'bg-amber-100 text-amber-700',
                                                    };
                                                @endphp
                                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded {{ $statusBadge }} flex-shrink-0">{{ ucfirst($item['payment_status']) }}</span>
                                            @endif
                                        </a>
                                        @php $flatIdx++; @endphp
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @endif
                </div>

                {{-- Footer keyboard hints --}}
                <div class="border-t border-gray-100 py-2 px-4 flex items-center justify-center gap-4">
                    <span class="text-[11px] text-gray-300">↑↓ navigate</span>
                    <span class="text-[11px] text-gray-300">↵ open</span>
                    <span class="text-[11px] text-gray-300">esc close</span>
                </div>
            </div>
        </div>
    @endif
</div>