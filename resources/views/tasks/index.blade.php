<x-layouts.app title="Tasks">

@php
    $sortLink = fn(string $col) => request()->fullUrlWithQuery([
        'sort'      => $col,
        'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
        'page'      => 1,
    ]);
@endphp

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px; font-weight:600; color:#141413">Tasks</h1>
    @if(auth()->user()->hasPermission('tasks.create'))
    <a href="{{ route('tasks.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New task
    </a>
    @endif
</div>

@include('components.flash')

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="direction" value="{{ $direction }}">

    @foreach([
        ['name' => 'status',   'label' => 'All Statuses',   'options' => $columns->pluck('name', 'slug')->toArray()],
        ['name' => 'priority', 'label' => 'All Priorities', 'options' => ['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low']],
        ['name' => 'type',     'label' => 'All Types',      'options' => ['bug' => 'Bug', 'feature' => 'Feature', 'change' => 'Change']],
        ['name' => 'project',  'label' => 'All Projects',   'options' => $projects->pluck('name', 'id')->toArray()],
        ['name' => 'assignee', 'label' => 'All Assignees',  'options' => $users->pluck('name', 'id')->toArray()],
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

    <label class="flex items-center gap-1.5 px-3 py-2 text-[13px] rounded-lg cursor-pointer"
           style="background:#F5F4EF; border:1px solid #e5e4df; color:#5c5c5a">
        <input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }} onchange="this.form.submit()">
        Overdue only
    </label>

    @if(request()->hasAny(['status','priority','type','project','assignee','overdue']))
        <a href="{{ route('tasks.index') }}" style="display:flex;align-items:center;padding:8px 12px;font-size:13px;color:#8c8c8a;text-decoration:none;border-radius:8px;transition:color 150ms ease"
           onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">
            x Clear filters
        </a>
    @endif
</form>

{{-- Table --}}
<div class="rounded-xl" style="background:#fff; border:1px solid #e5e4df; box-shadow:0 1px 3px rgba(20,20,19,0.04)">
<div class="overflow-x-auto">
    @if($tasks->isEmpty())
        <div class="py-16 text-center" style="color:#8c8c8a; font-size:13px">No tasks found.</div>
    @else
    <table class="w-full" style="font-size:13.5px; min-width:700px">
        <thead>
            <tr style="background:#faf9f5; border-bottom:1px solid #e5e4df">
                @php
                    $headers = [
                        ['col' => 'title',      'label' => 'Task',     'cls' => 'px-6 py-3 text-left'],
                        ['col' => null,         'label' => 'Type',     'cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                        ['col' => 'priority',   'label' => 'Priority', 'cls' => 'px-4 py-3 text-left'],
                        ['col' => 'status',     'label' => 'Status',   'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,         'label' => 'Assignee', 'cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                        ['col' => 'due_date',   'label' => 'Due',      'cls' => 'px-4 py-3 text-left'],
                        ['col' => 'created_at', 'label' => 'Created',  'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,         'label' => '',         'cls' => 'px-4 py-3'],
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
            @foreach($tasks as $task)
            <tr style="background:{{ $task->isOverdue() ? '#fff8f8' : '#fff' }};border-bottom:1px solid #eeeee9;transition:background 120ms ease"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='{{ $task->isOverdue() ? '#fff8f8' : '#fff' }}'">
                <td class="px-6 py-3">
                    <a href="{{ route('tasks.show', $task) }}" style="font-weight:500;color:#141413;text-decoration:none" onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">{{ $task->title }}</a>
                    @if($task->project)<p style="font-size:11px;color:#8c8c8a;margin-top:2px">{{ $task->project->name }}</p>@endif
                </td>
                <td class="px-4 py-3 hidden sm:table-cell">@include('components.badge', ['type' => 'type', 'value' => $task->type])</td>
                <td class="px-4 py-3">@include('components.badge', ['type' => 'priority', 'value' => $task->priority])</td>
                <td class="px-4 py-3">@include('components.badge', ['type' => 'status', 'value' => $task->status])</td>
                <td class="px-4 py-3 hidden sm:table-cell" style="color:#5c5c5a">
                    @if($task->assignee)
                        {{ $task->assignee->name }}
                    @elseif(\Illuminate\Support\Facades\Gate::allows('claim', $task))
                        <form method="POST" action="{{ route('tasks.claim', $task) }}" style="display:inline">
                            @csrf
                            <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#2e7d55;background:#edf7f2;border:1px solid #b7e0ca;border-radius:5px;padding:2px 8px;cursor:pointer;transition:background 120ms ease"
                                    onmouseover="this.style.background='#d6f0e4'"
                                    onmouseout="this.style.background='#edf7f2'"
                                    title="Assign this task to yourself">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width:11px;height:11px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59"/>
                                </svg>
                                Take it
                            </button>
                        </form>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3" style="color:{{ $task->isOverdue() ? '#b94040' : '#5c5c5a' }};font-weight:{{ $task->isOverdue() ? '500' : '400' }}">{{ $task->due_date?->format('M d') ?? '-' }}</td>
                <td class="px-4 py-3" style="color:#8c8c8a;font-size:12px">{{ $task->created_at->format('M d, Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('tasks.show', $task) }}" title="View" style="color:#8c8c8a;transition:color 120ms ease" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'eye'])</a>
                        <form method="POST" action="{{ route('tasks.advance', $task) }}" class="inline">@csrf @method('PATCH')
                            <button type="submit" style="color:#8c8c8a;background:none;border:none;cursor:pointer;padding:0;transition:color 120ms ease" onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => match($task->status) { 'backlog' => 'play', 'in-progress' => 'check', default => 'arrow-back' }])</button>
                        </form>
                        @if(auth()->user()->hasPermission('tasks.edit_any'))
                        <a href="{{ route('tasks.edit', $task) }}" title="Edit" style="color:#8c8c8a;transition:color 120ms ease" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'pencil'])</a>
                        @endif
                        @if(auth()->user()->hasPermission('tasks.delete'))
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')" class="inline">@csrf @method('DELETE')
                            <button type="submit" style="color:#8c8c8a;background:none;border:none;cursor:pointer;padding:0;transition:color 120ms ease" onmouseover="this.style.color='#b94040'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'trash'])</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
<div class="mt-4 px-4 pb-4" style="border-top:1px solid #eeeee9">{{ $tasks->links() }}</div>
</div>

</x-layouts.app>
