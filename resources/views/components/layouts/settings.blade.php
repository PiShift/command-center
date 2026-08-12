@props([
    'title' => 'Settings',
])

<x-layouts.app :title="$title">
    <div class="flex gap-6 items-start">
        <aside class="w-56 shrink-0 rounded-xl border border-line bg-white shadow-card overflow-hidden">
            <div class="px-4 py-3 border-b border-hairline bg-canvas">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Settings</p>
            </div>
            <nav class="p-2 space-y-1">
                <a href="{{ route('settings.checklist-templates.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('settings.checklist-templates.*') ? 'bg-accent-light text-accent' : 'text-dim hover:bg-hairline hover:text-ink' }}">
                    @include('components.icon', ['name' => 'clipboard'])
                    <span>Checklist Templates</span>
                </a>
                <a href="{{ route('settings.task-components.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('settings.task-components.*') ? 'bg-accent-light text-accent' : 'text-dim hover:bg-hairline hover:text-ink' }}">
                    @include('components.icon', ['name' => 'folder'])
                    <span>Components</span>
                </a>
                @if(auth()->user()?->hasPermission('settings.manage'))
                <a href="{{ route('skills.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('skills.*') ? 'bg-accent-light text-accent' : 'text-dim hover:bg-hairline hover:text-ink' }}">
                    @include('components.icon', ['name' => 'book'])
                    <span>Skills</span>
                </a>
                <a href="{{ route('runtimes.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('runtimes.*') ? 'bg-accent-light text-accent' : 'text-dim hover:bg-hairline hover:text-ink' }}">
                    @include('components.icon', ['name' => 'play'])
                    <span>Runtimes</span>
                </a>
                <a href="{{ route('settings.notifications') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('settings.notifications') || request()->routeIs('settings.notifications.*') ? 'bg-accent-light text-accent' : 'text-dim hover:bg-hairline hover:text-ink' }}">
                    @include('components.icon', ['name' => 'shield'])
                    <span>Notifications</span>
                </a>
                @endif
            </nav>
        </aside>

        <div class="flex-1 min-w-0">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
