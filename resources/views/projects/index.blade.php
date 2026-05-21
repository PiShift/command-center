<x-layouts.app title="Projects">

@php
    $sortLink = fn(string $col) => request()->fullUrlWithQuery([
        'sort'      => $col,
        'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
        'page'      => 1,
    ]);
@endphp

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px; font-weight:600; color:#141413">Projects</h1>
    @if(auth()->user()->hasPermission('projects.create'))
    <a href="{{ route('projects.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New project
    </a>
    @endif
</div>

@include('components.flash')

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="direction" value="{{ $direction }}">

    <div class="relative">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search projects..."
               class="text-[13px] pl-3 pr-3 py-2 rounded-lg"
               style="background:#F5F4EF; border:1px solid #e5e4df; color:#141413; outline:none; width:180px">
    </div>

    @foreach([
        ['name' => 'status',   'label' => 'All Statuses', 'options' => ['active' => 'Active', 'paused' => 'Paused', 'complete' => 'Complete']],
        ['name' => 'health',   'label' => 'All Health',   'options' => ['on-track' => 'On Track', 'at-risk' => 'At Risk', 'blocked' => 'Blocked']],
        ['name' => 'customer', 'label' => 'All Customers','options' => $customers->pluck('name', 'id')->toArray()],
    ] as $f)
    <div class="relative">
        <select name="{{ $f['name'] }}" onchange="this.form.submit()"
                class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer"
                style="background:#F5F4EF; border:1px solid #e5e4df; color:#141413; outline:none">
            <option value="">{{ $f['label'] }}</option>
            @foreach($f['options'] as $val => $lab)
                <option value="{{ $val }}" {{ request($f['name']) == $val ? 'selected' : '' }}>{{ $lab }}</option>
            @endforeach
        </select>
        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </div>
    @endforeach

    @if(request()->hasAny(['search','status','health','customer']))
        <a href="{{ route('projects.index') }}" style="display:flex;align-items:center;padding:8px 12px;font-size:13px;color:#8c8c8a;text-decoration:none;border-radius:8px"
           onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">x Clear</a>
    @endif
</form>

{{-- Table --}}
<div class="rounded-xl overflow-hidden" style="background:#fff; border:1px solid #e5e4df; box-shadow:0 1px 3px rgba(20,20,19,0.04)">
    @if($projects->isEmpty())
        <div class="py-16 text-center" style="color:#8c8c8a; font-size:13px">No projects yet.</div>
    @else
    <table class="w-full" style="font-size:13.5px">
        <thead>
            <tr style="background:#faf9f5; border-bottom:1px solid #e5e4df">
                @php
                    $headers = [
                        ['col' => 'name',       'label' => 'Project',        'cls' => 'px-6 py-3 text-left'],
                        ['col' => null,         'label' => 'Customer',       'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,         'label' => 'Activity',       'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,         'label' => 'Current Sprint', 'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,         'label' => 'Progress',       'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,         'label' => 'Health',         'cls' => 'px-4 py-3 text-left'],
                        ['col' => 'deadline',   'label' => 'Deadline',       'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,         'label' => '',               'cls' => 'px-4 py-3'],
                    ];
                @endphp
                @foreach($headers as $th)
                <th class="{{ $th['cls'] }}" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;white-space:nowrap">
                    @if($th['col'])
                        <a href="{{ $sortLink($th['col']) }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            {{ $th['label'] }}
                            <span style="color:{{ $sort === $th['col'] ? '#D97757' : '#d8d7d2' }}">{!! $sort === $th['col'] ? ($direction === 'asc' ? '↑' : '↓') : '↕' !!}</span>
                        </a>
                    @else
                        {{ $th['label'] }}
                    @endif
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
            <tr style="background:#fff;border-bottom:1px solid #eeeee9;transition:background 120ms ease"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='#fff'">
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $project->color ?? '#8c8c8a' }}"></span>
                        <a href="{{ route('projects.show', $project) }}" style="font-weight:500;color:#141413;text-decoration:none" onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">{{ $project->name }}</a>
                    </div>
                </td>
                <td class="px-4 py-3" style="color:#5c5c5a">{{ $project->customer?->name ?? '-' }}</td>
                {{-- Activity state --}}
                <td class="px-4 py-3">
                    @php
                        $actState = $project->activity_state ?? 'no_sprints';
                        $actConfig = match($actState) {
                            'active_sprint' => ['dot' => '#3d9970', 'pulse' => true,  'label' => 'Active sprint', 'textColor' => '#2e7d55'],
                            'preparing'     => ['dot' => '#4a90d9', 'pulse' => false, 'label' => 'Preparing',     'textColor' => '#3a6fba'],
                            'idle'          => ['dot' => '#e07b39', 'pulse' => false, 'label' => 'Idle',          'textColor' => '#9a6030'],
                            default         => ['dot' => '#c0bfba', 'pulse' => false, 'label' => 'No sprints',    'textColor' => '#8c8c8a'],
                        };
                    @endphp
                    <div class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2 shrink-0">
                            @if($actConfig['pulse'])
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-60" style="background:{{ $actConfig['dot'] }}"></span>
                            @endif
                            <span class="relative inline-flex rounded-full h-2 w-2" style="background:{{ $actConfig['dot'] }}"></span>
                        </span>
                        <span style="font-size:12px;font-weight:500;color:{{ $actConfig['textColor'] }}">{{ $actConfig['label'] }}</span>
                    </div>
                </td>

                {{-- Current Sprint --}}
                <td class="px-4 py-3">
                    @php
                        $sprintName    = $project->active_sprint_name ?? null;
                        $isDraft       = $project->active_sprint_is_draft ?? false;
                        $daysLeft      = $project->active_sprint_days_remaining ?? null;
                        $deadlineDate  = $project->active_sprint_deadline;
                    @endphp
                    @if($sprintName && !$isDraft)
                        <div style="font-size:13px;font-weight:500;color:#141413">{{ $sprintName }}</div>
                        @if($deadlineDate)
                        @php
                            $dlFmt = is_string($deadlineDate) ? \Carbon\Carbon::parse($deadlineDate) : $deadlineDate;
                            $daysColor = $daysLeft !== null ? ($daysLeft < 3 ? '#b94040' : ($daysLeft < 7 ? '#9a6030' : '#2e7d55')) : '#8c8c8a';
                        @endphp
                        <div style="font-size:11px;color:#8c8c8a;margin-top:2px">
                            {{ $dlFmt->format('M j') }}
                            @if($daysLeft !== null)
                                · <span style="font-weight:600;color:{{ $daysColor }}">{{ $daysLeft >= 0 ? $daysLeft . 'd left' : abs($daysLeft) . 'd overdue' }}</span>
                            @endif
                        </div>
                        @endif
                    @elseif($sprintName && $isDraft)
                        <div style="font-size:13px;font-style:italic;color:#3a6fba">{{ $sprintName }}</div>
                        <div style="font-size:11px;color:#8c8c8a;margin-top:2px">(Draft)</div>
                    @else
                        <span style="color:#9a6030;font-weight:500;font-size:13px">—</span>
                    @endif
                </td>

                {{-- Progress --}}
                <td class="px-4 py-3">
                    @php
                        $done    = $project->tasks_done_count ?? 0;
                        $total   = $project->tasks_total_count ?? 0;
                        $pct     = $project->tasks_progress_percent ?? 0;
                        $barColor = $pct >= 100 ? '#3d9970' : ($pct > 0 ? '#4a90d9' : '#d8d7d2');
                    @endphp
                    @if($total === 0)
                        <span style="font-size:12px;color:#8c8c8a">No tasks</span>
                    @else
                        <div style="font-size:12px;font-weight:500;color:#5c5c5a;margin-bottom:4px">{{ $done }} / {{ $total }}</div>
                        <div style="width:80px;height:4px;background:#eeeee9;border-radius:9999px;overflow:hidden">
                            <div style="width:{{ $pct }}%;height:100%;background:{{ $barColor }};border-radius:9999px"></div>
                        </div>
                    @endif
                </td>

                <td class="px-4 py-3">@include('components.badge', ['type' => 'health', 'value' => $project->health ?? 'on-track'])</td>
                <td class="px-4 py-3" style="color:{{ $project->isOverdue() ? '#b94040' : '#5c5c5a' }};font-weight:{{ $project->isOverdue() ? '500' : '400' }}">{{ $project->deadline?->format('M d, Y') ?? '-' }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if(auth()->user()->hasPermission('projects.edit'))
                        <a href="{{ route('projects.edit', $project) }}" title="Edit" style="color:#8c8c8a;transition:color 120ms ease" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'pencil'])</a>
                        @endif
                        @if(auth()->user()->hasPermission('projects.delete'))
                        <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project?')" class="inline">@csrf @method('DELETE')
                            <button type="submit" style="color:#8c8c8a;background:none;border:none;cursor:pointer;padding:0;transition:color 120ms ease" onmouseover="this.style.color='#b94040'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'trash'])</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 flex items-center justify-between" style="border-top:1px solid #eeeee9">
        <div>{{ $projects->links() }}</div>
        @if(!$sort)
        <span style="font-size:11px;color:#8c8c8a">Sorted by activity</span>
        @endif
    </div>
    @endif
</div>

</x-layouts.app>
