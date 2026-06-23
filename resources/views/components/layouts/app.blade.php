@php $isDeveloper = auth()->user()?->roleModel?->slug === 'developer'; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      :class="{ 'sidebar-collapsed': !$store.sidebar.isOpen, 'sidebar-open': $store.sidebar.isOpen }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' . config('app.name') : config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/favicon/site.webmanifest">
    <meta name="theme-color" content="#D97757">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* ── Mobile sidebar: fixed overlay, zero impact on flex layout ── */
        @media (max-width: 1023px) {
            .sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                z-index: 30;
                transform: translateX(-100%);
                transition: transform 0.22s cubic-bezier(0.4,0,0.2,1) !important;
            }
            .sidebar-open .sidebar {
                transform: translateX(0);
            }
        }
        /* ── AI typing indicator bounce ── */
        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: 0.5; }
            40%            { transform: translateY(-4px); opacity: 1; }
        }
    </style>
</head>
<body class="bg-canvas text-ink antialiased min-h-screen">

<div class="flex h-screen overflow-hidden">

    {{-- ── Sidebar (hidden for Developer) ──────────── --}}
    @if(!$isDeveloper)
    {{-- Mobile backdrop — closes sidebar when tapped --}}
    <div
        x-show="$store.sidebar.isOpen"
        x-cloak
        @click="$store.sidebar.close()"
        class="fixed inset-0 bg-black/50 z-20 lg:hidden">
    </div>
    @include('components.sidebar')
    @endif

    {{-- ── Main content ──────────────────────────── --}}
    <div class="flex flex-col flex-1 overflow-hidden">
        @if(!$isDeveloper)
        @include('components.topbar')
        @else
        {{-- Developer top bar --}}
        <div class="h-[52px] flex items-center px-6 bg-white border-b border-line shrink-0">
            <div class="flex items-center gap-2">
                <img src="/images/icon-wb-round.webp" alt="CC" class="w-8 h-8 rounded-lg shrink-0">
                <img src="/images/logo.svg" alt="{{ config('app.name') }}" class="h-[20px] object-contain">
            </div>
            <a href="{{ route('board') }}" class="ml-4 text-[11px] font-bold uppercase tracking-widest text-muted hover:text-ink transition-colors">My Board</a>

            <span class="ml-4 text-[10px] font-bold uppercase tracking-widest text-muted">Configure</span>
            <a href="{{ route('agents.index') }}"
               class="ml-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[11px] font-bold uppercase tracking-widest transition-colors {{ request()->routeIs('agents.*') ? 'bg-accent-light text-accent' : 'text-muted hover:text-ink hover:bg-hairline' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 7h14M5 12h14M5 17h14"/>
                    <circle cx="8" cy="7" r="1" fill="currentColor"/>
                </svg>
                Agents
            </a>
            <a href="{{ route('runtimes.index') }}"
               class="ml-1 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[11px] font-bold uppercase tracking-widest transition-colors {{ request()->routeIs('runtimes.*') ? 'bg-accent-light text-accent' : 'text-muted hover:text-ink hover:bg-hairline' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="12" rx="2"/>
                    <path d="M8 20h8"/>
                </svg>
                Runtimes
            </a>
            <a href="{{ route('skills.index') }}"
               class="ml-1 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[11px] font-bold uppercase tracking-widest transition-colors {{ request()->routeIs('skills.*') ? 'bg-accent-light text-accent' : 'text-muted hover:text-ink hover:bg-hairline' }}">
                @include('components.icon', ['name' => 'book'])
                Skills
            </a>
            <div class="ml-auto flex items-center gap-3">
                <button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-global-search'))"
                    class="hidden md:flex items-center gap-2 w-56 px-3 py-2 bg-surface border border-line rounded-lg text-[13px] text-muted hover:text-dim hover:border-muted transition-colors cursor-pointer"
                    aria-label="Open global search"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                    </svg>
                    <span class="flex-1 text-left">Search...</span>
                    <span class="px-1.5 py-0.5 text-[11px] font-semibold text-muted bg-canvas border border-line rounded">⌘/</span>
                </button>

                <livewire:notification-bell />

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 text-[13px] text-dim hover:text-ink transition-colors">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-semibold text-white"
                              style="background: {{ auth()->user()->color ?? '#D97757' }}">
                            {{ auth()->user()->initials ?? strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </span>
                        <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 top-9 w-44 bg-white border border-line rounded-lg shadow-lg py-1 z-50">
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-2 w-full px-4 py-2 text-[13px] text-dim hover:bg-hairline hover:text-ink">
                            @include('components.icon', ['name' => 'user'])
                            My Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 w-full text-left px-4 py-2 text-[13px] text-dim hover:bg-hairline hover:text-ink">
                                @include('components.icon', ['name' => 'log-out'])
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>

</div>

@livewire('ai-chat-panel')
@livewire('quick-capture')
@livewire('global-search')
@livewireScripts
@stack('scripts')
<script>
(function () {
    var _pendingN = false;
    var _nTimer   = null;

    function dispatchProjectHints() {
        var path = window.location.pathname || '';
        var match = path.match(/^\/projects\/(\d+)(?:\/.*)?$/);
        var projectId = match ? parseInt(match[1], 10) : NaN;

        if (!Number.isFinite(projectId)) {
            var stored = parseInt(localStorage.getItem('recent_project_id') || '', 10);
            if (Number.isFinite(stored)) {
                projectId = stored;
            }
        }

        if (Number.isFinite(projectId)) {
            localStorage.setItem('recent_project_id', String(projectId));
            window.dispatchEvent(new CustomEvent('ai-project-hint', { detail: { projectId: projectId } }));
            window.dispatchEvent(new CustomEvent('quick-capture-project-hint', { detail: { projectId: projectId } }));
        }
    }

    dispatchProjectHints();
    window.addEventListener('popstate', dispatchProjectHints);

    document.addEventListener('keydown', function (e) {
        // ── Always-on shortcuts (work even inside inputs) ────────────
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('open-ai-chat'));
            return;
        }
        if ((e.metaKey || e.ctrlKey) && e.key === '/') {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('open-global-search'));
            return;
        }
        if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toUpperCase() === 'N') {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('open-quick-capture'));
            return;
        }

        // ── Guard: skip when an input is focused ──────────────────────
        var tag = document.activeElement ? document.activeElement.tagName : '';
        var editable = document.activeElement && document.activeElement.contentEditable === 'true';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || editable) return;

        // ── Cmd/Ctrl + Shift shortcuts ────────────────────────────────
        if ((e.metaKey || e.ctrlKey) && e.shiftKey) {
            switch (e.key.toUpperCase()) {
                case 'P':
                    e.preventDefault();
                    window.location.href = '/projects/create';
                    return;
                case 'C':
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-quick-customer-modal'));
                    return;
                case 'I':
                    e.preventDefault();
                    window.location.href = '/invoices/create';
                    return;
                case 'D':
                    e.preventDefault();
                    window.location.href = '/dashboard';
                    return;
                case 'B':
                    e.preventDefault();
                    window.location.href = '/board';
                    return;
                case 'T':
                    e.preventDefault();
                    window.location.href = '/tasks';
                    return;
                case 'E':
                    e.preventDefault();
                    window.location.href = '/expenses';
                    return;
            }
            return;
        }

        // ── n → x two-key sequence ────────────────────────────────────
        if (!e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey) {
            if (e.key === 'n') {
                _pendingN = true;
                clearTimeout(_nTimer);
                _nTimer = setTimeout(function () { _pendingN = false; }, 700);
                return;
            }
            if (_pendingN) {
                clearTimeout(_nTimer);
                _pendingN = false;
                if (e.key === 'p') {
                    e.preventDefault();
                    window.location.href = '/projects/create';
                } else if (e.key === 'c') {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-quick-customer-modal'));
                } else if (e.key === 'i') {
                    e.preventDefault();
                    window.location.href = '/invoices/create';
                }
            }
        }
    });
}());
</script>
@include('components.quick-customer-modal')
</body>
</html>
