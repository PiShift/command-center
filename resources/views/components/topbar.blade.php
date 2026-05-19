{{-- Top bar --}}
<header class="h-14 border-b border-line flex items-center justify-between px-6 shrink-0">

    {{-- Page title --}}
    <h1 class="text-[15px] font-semibold text-ink">
        {{ $title ?? (View::hasSection('title') ? View::getSection('title') : '') }}
    </h1>

    {{-- Right side: user menu --}}
    <div class="flex items-center gap-3">
        @auth
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
