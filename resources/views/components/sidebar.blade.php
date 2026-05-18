{{--
  Sidebar — pure HTML + CSS, no Filament.
  Collapse driven by .sidebar-collapsed class on <html> (set by Alpine store).
--}}

@php
    $user = auth()->user();
    $nav = [
        ['route' => 'dashboard', 'label' => 'War Room',  'icon' => 'grid'],
    ];
    if ($user->hasPermission('tasks.view')) {
        $nav[] = ['route' => 'board', 'label' => 'Board', 'icon' => 'columns', 'badge' => \App\Models\Task::where('status','!=','done')->count()];
        $nav[] = ['route' => 'tasks.index', 'label' => 'Tasks', 'icon' => 'clipboard', 'badge' => \App\Models\Task::where('status','!=','done')->where(fn($q)=>$q->where('priority','high')->orWhere(fn($q2)=>$q2->whereNotNull('due_date')->whereDate('due_date','<',now())))->count()];
    }
    if ($user->hasPermission('projects.view')) {
        $nav[] = ['route' => 'projects.index', 'label' => 'Projects', 'icon' => 'folder', 'badge' => \App\Models\Project::count()];
    }
    if ($user->hasPermission('users.view')) {
        $nav[] = ['route' => 'users.index', 'label' => 'Users', 'icon' => 'users'];
    }
    if ($user->hasPermission('teams.view')) {
        $nav[] = ['route' => 'teams.index', 'label' => 'Teams', 'icon' => 'layers', 'badge' => \App\Models\Team::count() ?: null];
    }
    if ($user->hasPermission('customers.view')) {
        $nav[] = ['route' => 'customers.index', 'label' => 'Customers', 'icon' => 'briefcase'];
    }
    if ($user->hasPermission('invoices.view')) {
        $nav[] = ['route' => 'invoices.index', 'label' => 'Invoices', 'icon' => 'file-text', 'badge' => \App\Models\Invoice::whereIn('status',['published','partially_paid'])->count() ?: null];
    }
    if ($user->hasPermission('payments.view')) {
        $nav[] = ['route' => 'payments.index', 'label' => 'Payments', 'icon' => 'credit-card'];
    }
    if ($user->hasPermission('roles.view')) {
        $nav[] = ['route' => 'roles.index', 'label' => 'Roles', 'icon' => 'shield'];
    }
    $projects = \App\Models\Project::orderBy('name')->get(['id', 'name', 'color']);
@endphp

<aside class="sidebar" id="sidebar">
    <style>
        .sidebar {
            width: 220px;
            min-width: 220px;
            height: 100vh;
            background: var(--color-surface);
            border-right: 1px solid var(--color-line);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width 0.22s cubic-bezier(0.4,0,0.2,1),
                        min-width 0.22s cubic-bezier(0.4,0,0.2,1);
            flex-shrink: 0;
        }
        .sidebar-collapsed .sidebar {
            width: 60px;
            min-width: 60px;
        }

        /* Labels fade out */
        .sidebar-label {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            max-width: 160px;
            opacity: 1;
            transition: max-width 0.22s cubic-bezier(0.4,0,0.2,1),
                        opacity 0.16s ease;
        }
        .sidebar-collapsed .sidebar-label {
            max-width: 0;
            opacity: 0;
        }

        /* Badges fade + collapse */
        .sidebar-badge {
            overflow: hidden;
            max-width: 40px;
            transition: max-width 0.22s cubic-bezier(0.4,0,0.2,1),
                        opacity 0.16s ease,
                        padding 0.22s cubic-bezier(0.4,0,0.2,1),
                        margin 0.22s cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar-collapsed .sidebar-badge {
            max-width: 0;
            opacity: 0;
            padding-left: 0;
            padding-right: 0;
        }

        /* Group headings */
        .sidebar-group-label {
            display: block;
            max-width: 160px;
            overflow: hidden;
            white-space: nowrap;
            opacity: 1;
            transition: max-width 0.22s cubic-bezier(0.4,0,0.2,1),
                        opacity 0.16s ease;
        }
        .sidebar-collapsed .sidebar-group-label {
            max-width: 0;
            opacity: 0;
        }

        /* Nav items */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 12px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 500;
            color: var(--color-dim);
            text-decoration: none;
            transition: background 0.12s, color 0.12s;
            cursor: pointer;
        }
        .nav-item:hover { background: var(--color-hairline); color: var(--color-ink); }
        .nav-item.active {
            background: var(--color-accent-light);
            color: var(--color-accent);
        }
        .nav-item svg { flex-shrink: 0; }

        /* Collapse toggle */
        .collapse-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: var(--color-muted);
            cursor: pointer;
            border-top: 1px solid var(--color-line);
            transition: color 0.12s;
        }
        .collapse-btn:hover { color: var(--color-ink); }
        .collapse-arrow {
            flex-shrink: 0;
            transition: transform 0.22s cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar-collapsed .collapse-arrow { transform: rotate(180deg); }

        /* Project dots */
        .project-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* Hide projects section when collapsed */
        .sidebar-projects {
            overflow: hidden;
            max-height: 9999px;
            opacity: 1;
            transition: max-height 0.22s cubic-bezier(0.4,0,0.2,1),
                        opacity 0.16s ease;
        }
        .sidebar-collapsed .sidebar-projects {
            max-height: 0;
            opacity: 0;
        }

        /* Tooltip */
        #sidebar-tooltip {
            position: fixed;
            z-index: 9999;
            pointer-events: none;
            background: #1e1e1c;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 7px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            white-space: nowrap;
            transform: translateY(-50%);
        }
        #sidebar-tooltip-arrow {
            position: absolute;
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
            width: 0; height: 0;
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
            border-right: 5px solid #1e1e1c;
        }
    </style>

    {{-- Logo --}}
    <div class="flex items-center gap-2 px-3 py-0" style="min-height:56px">
        <img src="/images/icon-wb-round.webp" alt="CC" class="w-8 h-8 rounded-md flex-shrink-0">
        <img src="/images/logo.svg" alt="Command Center" class="sidebar-label h-[22px] object-contain object-left" style="padding:0">
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-2 pb-2 space-y-0.5">

        <p class="sidebar-group-label text-[10px] font-semibold uppercase tracking-wider text-muted px-2 pb-1 pt-2">Workspace</p>

        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}"
               @mouseenter="$store.sidebarTip.open($el, '{{ $item['label'] }}')"
               @mouseleave="$store.sidebarTip.close()"
               class="nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                @include('components.icon', ['name' => $item['icon']])
                <span class="sidebar-label flex-1">{{ $item['label'] }}</span>
                @if (!empty($item['badge']) && $item['badge'] > 0)
                    <span class="sidebar-badge text-[10px] font-semibold px-1.5 py-0.5 rounded-full text-white" style="background:var(--color-accent)">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach

        {{-- Projects section --}}
        @if ($projects->isNotEmpty())
            <div class="sidebar-projects pt-3">
                <p class="sidebar-group-label text-[10px] font-semibold uppercase tracking-wider text-muted px-2 pb-1">Projects</p>
                @foreach ($projects as $project)
                    <a href="{{ route('projects.show', $project) }}"
                       @mouseenter="$store.sidebarTip.open($el, '{{ $project->name }}')"
                       @mouseleave="$store.sidebarTip.close()"
                       class="nav-item {{ request()->routeIs('projects.show') && request()->route('project')?->id == $project->id ? 'active' : '' }}">
                        <span class="project-dot flex-shrink-0" style="background: {{ $project->color ?? '#aaa' }}"></span>
                        <span class="sidebar-label">{{ $project->name }}</span>
                    </a>
                @endforeach
            </div>
        @endif

    </nav>

    {{-- Collapse toggle --}}
    <div class="collapse-btn" onclick="Alpine.store('sidebar').toggle()">
        <svg class="collapse-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span class="sidebar-label text-[12px]">Collapse</span>
    </div>

</aside>

{{-- Tooltip lives outside <aside> so it isn't clipped by overflow:hidden --}}
<div id="sidebar-tooltip"
     x-data
     x-show="$store.sidebarTip.show"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     :style="`top:${$store.sidebarTip.y}px;left:${$store.sidebarTip.x}px`"
     style="display:none">
    <div id="sidebar-tooltip-arrow"></div>
    <span x-text="$store.sidebarTip.text"></span>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebarTip', {
            show: false,
            text: '',
            x: 0,
            y: 0,
            open(el, label) {
                if (!document.documentElement.classList.contains('sidebar-collapsed')) return;
                const rect = el.getBoundingClientRect();
                this.text = label;
                this.x = rect.right + 10;
                this.y = rect.top + rect.height / 2;
                this.show = true;
            },
            close() { this.show = false; }
        });
    });
</script>
