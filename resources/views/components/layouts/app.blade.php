@php $isDeveloper = auth()->user()?->roleModel?->slug === 'developer'; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      :class="$store.sidebar.isOpen ? '' : 'sidebar-collapsed'">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — Command Center' : 'Command Center' }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/favicon/site.webmanifest">
    <meta name="theme-color" content="#D97757">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-canvas text-ink antialiased min-h-screen">

<div class="flex h-screen overflow-hidden">

    {{-- ── Sidebar (hidden for Developer) ──────────── --}}
    @if(!$isDeveloper)
    @include('components.sidebar')
    @endif

    {{-- ── Main content ──────────────────────────── --}}
    <div class="flex flex-col flex-1 overflow-hidden">
        @if(!$isDeveloper)
        @include('components.topbar')
        @else
        {{-- Minimal top bar for Developer --}}
        <div class="h-[52px] flex items-center px-6 bg-white border-b border-line shrink-0">
            <div class="flex items-center gap-2">
                <img src="/images/icon-wb-round.webp" alt="CC" class="w-8 h-8 rounded-lg shrink-0">
                <img src="/images/logo.svg" alt="PiShift" class="h-[20px] object-contain">
            </div>
            <span class="ml-4 text-[11px] font-bold uppercase tracking-widest text-muted">My Board</span>
            <div class="ml-auto flex items-center gap-3">
                <span class="text-[13px] text-dim">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="text-[12px] text-muted bg-transparent border-none cursor-pointer px-2 py-1 rounded-md transition-colors duration-150 hover:bg-hairline hover:text-ink">Sign out</button>
                </form>
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
