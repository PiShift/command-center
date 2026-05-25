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
            <div class="ml-auto flex items-center gap-3">
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

@livewireScripts
@stack('scripts')
</body>
</html>
