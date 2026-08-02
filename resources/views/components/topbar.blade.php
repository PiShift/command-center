{{-- Top bar --}}
<header class="h-14 border-b border-line flex items-center justify-between px-6 shrink-0">

    <div class="flex items-center gap-3">
        {{-- Hamburger — mobile only --}}
        <button @click="$store.sidebar.toggle()"
                class="lg:hidden flex items-center justify-center w-8 h-8 rounded-lg text-dim hover:text-ink hover:bg-hairline transition-colors cursor-pointer"
                aria-label="Toggle sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Page title --}}
        <h1 class="text-[15px] font-semibold text-ink">
        {{ $title ?? (View::hasSection('title') ? View::getSection('title') : '') }}
        </h1>
    </div>

    {{-- Right side: user menu --}}
    <div class="flex items-center gap-3">
        @auth
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-global-search'))"
                class="hidden md:flex items-center gap-2 w-56 px-3 py-2 bg-surface border border-line rounded-lg text-[13px] text-muted hover:text-dim hover:border-muted transition-colors cursor-pointer"
                aria-label="Open global search"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                </svg>
                <span class="flex-1 text-left">
                    @php $lastSearch = session('recent_searches')[0] ?? null; @endphp
                    @if($lastSearch)Search "{{ Str::limit($lastSearch, 18) }}"...@else Search...@endif
                </span>
                <span class="px-1.5 py-0.5 text-[11px] font-semibold text-muted bg-canvas border border-line rounded">⌘/</span>
            </button>

            {{-- Notification Bell --}}
            <livewire:notification-bell />

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 text-[13px] text-dim hover:text-ink transition-colors">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-semibold text-white"
                          style="background: {{ auth()->user()->color ?? '#D97757' }}">
                        {{ auth()->user()->initials ?? strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                    <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                    @include('components.icon', ['name' => 'chevron-down'])
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
        @endauth
    </div>
</header>
