<x-filament-panels::page>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<style>
    /* ── Merge filters + action buttons into one top bar ── */

    /* Give the section a positioning context and clear the top bar height */
    section.flex {
        position: relative !important;
        padding-top: 56px !important;
        padding-bottom: 0 !important;
        gap: 12px !important;
    }

    /* Float the Filament header (actions) to the right of the top bar */
    .fi-header {
        position: absolute !important;
        top: 8px !important;
        right: 0 !important;
        left: auto !important;
        gap: 0 !important;
    }

    /* Hide heading/breadcrumb — the left side */
    .fi-header > div:first-child { display:none !important; }

    /* Float our filter bar to the LEFT of that same top bar */
    .wr-filters {
        position: absolute !important;
        top: 8px !important;
        left: 0 !important;
        margin-bottom: 0 !important;
    }

    /* ── Board ── */
    .wr-board { display:flex; gap:20px; overflow-x:auto; overflow-y:hidden; padding-bottom:24px; align-items:flex-start; }
    .wr-col { display:flex; flex-direction:column; background:#F5F4EF; border-radius:12px; min-width:300px; max-width:300px; max-height:calc(100vh - 160px); }
    .wr-col-header { display:flex; align-items:center; justify-content:space-between; padding:14px 16px 10px; flex-shrink:0; }
    .wr-col-body { flex:1; overflow-y:auto; padding:0 10px 10px; display:flex; flex-direction:column; gap:8px; }

    /* ── Cards ── */
    .wr-card { background:white; border-radius:10px; padding:13px 14px; cursor:grab; transition:box-shadow 0.15s; }
    .wr-card:active { cursor:grabbing; }
    .wr-card:hover { box-shadow:0 4px 16px rgba(20,20,19,0.10); }

    /* ── Drag states ── */
    .sortable-ghost  { opacity:0.3; }
    .sortable-chosen { box-shadow:0 10px 30px rgba(20,20,19,0.18); transform:rotate(1deg) scale(1.02); }
    .sortable-drag   { opacity:0; }
    .wr-col-ghost    { opacity:0.4; }
    .wr-col-chosen   { box-shadow:0 12px 32px rgba(20,20,19,0.22); transform:rotate(-1deg) scale(1.01); }

    /* ── Priority badges ── */
    .badge-high   { background:#fee2e2; color:#b91c1c; }
    .badge-medium { background:#fef3c7; color:#92400e; }
    .badge-low    { background:#dcfce7; color:#166534; }

    /* ── Column name input ── */
    .col-name-input { background:transparent; border:none; outline:none; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#5c5c5a; width:100%; cursor:text; }
    .col-name-input:focus { border-bottom:1px dashed #aaa; }

    /* ── Empty state & add button ── */
    .wr-empty { border:2px dashed #e5e4df; border-radius:8px; padding:32px 16px; text-align:center; color:#c4c4c0; font-size:12px; }
    .wr-add-btn { width:100%; background:transparent; border:2px dashed #e5e4df; border-radius:8px; padding:10px; color:#8c8c8a; font-size:13px; cursor:pointer; transition:border-color .15s,color .15s; margin-top:4px; font-family:inherit; }
    .wr-add-btn:hover { border-color:#D97757; color:#D97757; }

    /* ── Searchable dropdown ── */
    .wr-dropdown { position:relative; display:inline-block; }
    .wr-dropdown-trigger {
        display:flex; align-items:center; gap:8px; padding:6px 12px;
        background:white; border:1px solid #e5e4df; border-radius:8px;
        font-size:13px; font-family:inherit; color:#141413; cursor:pointer;
        white-space:nowrap; transition:border-color .15s,box-shadow .15s;
        user-select:none;
    }
    .wr-dropdown-trigger:hover { border-color:#D97757; }
    .wr-dropdown-trigger.is-open { border-color:#D97757; box-shadow:0 0 0 3px rgba(217,119,87,.12); }
    .wr-dropdown-trigger .wr-chevron {
        width:14px; height:14px; color:#8c8c8a; flex-shrink:0;
        transition:transform .15s;
    }
    .wr-dropdown-trigger.is-open .wr-chevron { transform:rotate(180deg); }
    .wr-dropdown-trigger .wr-dot {
        width:8px; height:8px; border-radius:50%; flex-shrink:0;
    }
    .wr-dropdown-panel {
        position:absolute; top:calc(100% + 6px); left:0; z-index:999;
        background:white; border:1px solid #e5e4df; border-radius:10px;
        box-shadow:0 8px 24px rgba(20,20,19,0.12); min-width:220px;
        overflow:hidden;
    }
    .wr-dropdown-search {
        display:flex; align-items:center; gap:8px; padding:8px 12px;
        border-bottom:1px solid #f0efeb;
    }
    .wr-dropdown-search input {
        border:none; outline:none; font-size:13px; font-family:inherit;
        color:#141413; width:100%; background:transparent;
    }
    .wr-dropdown-search input::placeholder { color:#c4c4c0; }
    .wr-dropdown-list { max-height:220px; overflow-y:auto; padding:4px; }
    .wr-dropdown-item {
        display:flex; align-items:center; gap:8px; padding:7px 10px;
        border-radius:6px; font-size:13px; color:#141413; cursor:pointer;
        transition:background .1s;
    }
    .wr-dropdown-item:hover { background:#f5f4ef; }
    .wr-dropdown-item.is-selected { background:#fff3ee; color:#D97757; font-weight:600; }
    .wr-dropdown-item .wr-check { width:14px; height:14px; flex-shrink:0; }
    .wr-dropdown-empty { padding:16px 12px; text-align:center; font-size:12px; color:#c4c4c0; }

    /* ── Filter bar ── */
    .wr-filters { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:14px; }
    .wr-task-count { font-size:12px; color:#8c8c8a; margin-left:4px; }

    /* ── Assignee dot ── */
    .wr-avatar { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; font-size:9px; font-weight:700; color:white; flex-shrink:0; }
</style>

@php
    $projectOptions = [['value' => '', 'label' => 'All Projects']] +
        $this->projects->map(fn($p) => ['value' => $p->id, 'label' => $p->name])->values()->toArray();

    $priorityOptions = [
        ['value' => '',       'label' => 'All Priorities', 'dot' => '#d1d5db'],
        ['value' => 'high',   'label' => 'High',           'dot' => '#ef4444'],
        ['value' => 'medium', 'label' => 'Medium',         'dot' => '#f59e0b'],
        ['value' => 'low',    'label' => 'Low',            'dot' => '#22c55e'],
    ];

    $currentProjectLabel = $this->projectFilter
        ? ($this->projects->firstWhere('id', $this->projectFilter)?->name ?? 'Project')
        : 'All Projects';

    $currentPriorityLabel = match($this->priorityFilter) {
        'high'   => 'High',
        'medium' => 'Medium',
        'low'    => 'Low',
        default  => 'All Priorities',
    };
    $currentPriorityDot = match($this->priorityFilter) {
        'high'   => '#ef4444',
        'medium' => '#f59e0b',
        'low'    => '#22c55e',
        default  => null,
    };
@endphp

{{-- ── Filter bar ────────────────────────────────────────────────────── --}}
<div class="wr-filters">

    {{-- Project dropdown --}}
    <div class="wr-dropdown"
         x-data="{
            open: false,
            search: '',
            selected: @js($this->projectFilter ? (int)$this->projectFilter : ''),
            label: @js($currentProjectLabel),
            options: @js($projectOptions),
            get filtered() {
                if (!this.search) return this.options;
                const q = this.search.toLowerCase();
                return this.options.filter(o => o.label.toLowerCase().includes(q));
            },
            choose(opt) {
                this.selected = opt.value;
                this.label = opt.label;
                this.open = false;
                this.search = '';
                $wire.set('projectFilter', opt.value === '' ? null : opt.value);
            }
         }"
         @keydown.escape="open = false"
         @click.outside="open = false">

        <button class="wr-dropdown-trigger" :class="{ 'is-open': open }" @click="open = !open" type="button">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M7 12h10M10 17h4"/>
            </svg>
            <span x-text="label"></span>
            <svg class="wr-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div class="wr-dropdown-panel" x-show="open" x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             style="display:none">
            <div class="wr-dropdown-search">
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model="search" placeholder="Search projects…" @click.stop x-ref="projectSearch" x-effect="if(open) $nextTick(() => $refs.projectSearch.focus())">
            </div>
            <div class="wr-dropdown-list">
                <template x-if="filtered.length === 0">
                    <div class="wr-dropdown-empty">No projects found</div>
                </template>
                <template x-for="opt in filtered" :key="opt.value">
                    <div class="wr-dropdown-item" :class="{ 'is-selected': selected == opt.value }" @click="choose(opt)">
                        <svg class="wr-check" x-show="selected == opt.value" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg class="wr-check" x-show="selected != opt.value" viewBox="0 0 24 24"></svg>
                        <span x-text="opt.label"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Priority dropdown --}}
    <div class="wr-dropdown"
         x-data="{
            open: false,
            search: '',
            selected: @js($this->priorityFilter),
            label: @js($currentPriorityLabel),
            dot: @js($currentPriorityDot),
            options: @js($priorityOptions),
            get filtered() {
                if (!this.search) return this.options;
                const q = this.search.toLowerCase();
                return this.options.filter(o => o.label.toLowerCase().includes(q));
            },
            choose(opt) {
                this.selected = opt.value;
                this.label = opt.label;
                this.dot = opt.dot ?? null;
                this.open = false;
                this.search = '';
                $wire.set('priorityFilter', opt.value);
            }
         }"
         @keydown.escape="open = false"
         @click.outside="open = false">

        <button class="wr-dropdown-trigger" :class="{ 'is-open': open }" @click="open = !open" type="button">
            <span class="wr-dot" x-show="dot" :style="'background:' + dot"></span>
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" x-show="!dot" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4l9 9v7l3-3v-4l9-9H3z"/>
            </svg>
            <span x-text="label"></span>
            <svg class="wr-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div class="wr-dropdown-panel" x-show="open" x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             style="display:none">
            <div class="wr-dropdown-search">
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model="search" placeholder="Search priority…" @click.stop x-ref="prioritySearch" x-effect="if(open) $nextTick(() => $refs.prioritySearch.focus())">
            </div>
            <div class="wr-dropdown-list">
                <template x-if="filtered.length === 0">
                    <div class="wr-dropdown-empty">No results</div>
                </template>
                <template x-for="opt in filtered" :key="opt.value">
                    <div class="wr-dropdown-item" :class="{ 'is-selected': selected === opt.value }" @click="choose(opt)">
                        <span class="wr-dot" x-show="opt.dot" :style="'background:' + opt.dot"></span>
                        <svg class="wr-check" x-show="!opt.dot" viewBox="0 0 24 24"></svg>
                        <span x-text="opt.label"></span>
                        <svg class="wr-check ml-auto" x-show="selected === opt.value" fill="none" stroke="#D97757" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @php $total = collect($this->columns)->sum(fn($c) => count($c['tasks'])) @endphp
    <span class="wr-task-count">{{ $total }} {{ Str::plural('task', $total) }}</span>
</div>

{{-- ── Board ──────────────────────────────────────────────────────────── --}}
<div id="kanban-board" class="wr-board">
    @foreach ($this->columns as $column)
        <div class="wr-col" data-col-id="{{ $column['id'] }}">

            <div class="wr-col-header col-drag-handle" style="cursor:grab">
                <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0">
                    <input
                        class="col-name-input"
                        value="{{ $column['label'] }}"
                        title="Click to rename"
                        onblur="handleColRename(this, {{ $column['id'] }})"
                        onkeydown="if(event.key==='Enter'||event.key==='Escape'){this.blur()}"
                        onclick="event.stopPropagation()"
                    >
                    <span style="background:white;color:#8c8c8a;font-size:11px;padding:2px 8px;border-radius:9999px;font-weight:500;flex-shrink:0">{{ count($column['tasks']) }}</span>
                </div>
                @if (! $column['is_protected'])
                    <button
                        onclick="event.stopPropagation(); confirmDeleteColumn({{ $column['id'] }}, '{{ addslashes($column['label']) }}')"
                        style="background:transparent;border:none;color:#c4c4c0;cursor:pointer;padding:2px 4px;font-size:16px;line-height:1"
                        title="Delete column"
                    >&times;</button>
                @endif
            </div>

            <div class="wr-col-body" data-status="{{ $column['key'] }}">
                @forelse ($column['tasks'] as $task)
                    @php $overdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'done'; @endphp
                    <div class="wr-card" data-task-id="{{ $task->id }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;gap:6px">
                            @if ($task->project)
                                <span style="font-size:11px;font-weight:600;color:#4a90d9;background:#eef4fd;padding:2px 8px;border-radius:9999px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $task->project->name }}</span>
                            @else
                                <span></span>
                            @endif
                            <span class="badge-{{ $task->priority ?? 'low' }}" style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:9999px;display:inline-flex;align-items:center;gap:3px;flex-shrink:0">
                                @php echo match($task->priority) { 'high' => '↑ High', 'medium' => '→ Med', 'low' => '↓ Low', default => ucfirst($task->priority ?? 'low') }; @endphp
                            </span>
                        </div>

                        <p style="font-size:14px;font-weight:600;color:#141413;line-height:1.35;margin-bottom:8px">
                            <a href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $task->id]) }}"
                               style="color:inherit;text-decoration:none"
                               onclick="event.stopPropagation()"
                               onmouseover="this.style.color='#D97757'"
                               onmouseout="this.style.color='#141413'">{{ $task->title }}</a>
                        </p>

                        @if ($task->description)
                            <p style="font-size:12px;color:#8c8c8a;margin-bottom:8px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $task->description }}</p>
                        @endif

                        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px">
                            <div>
                                @if ($task->due_date)
                                    <span style="font-size:11px;color:{{ $overdue ? '#b91c1c' : '#8c8c8a' }};font-weight:{{ $overdue ? '600' : '400' }}">
                                        {{ $overdue ? '⚠' : '📅' }} {{ $task->due_date->format('M j') }}
                                    </span>
                                @endif
                            </div>
                            @if ($task->assignee)
                                <span class="wr-avatar" style="background:{{ $task->assignee->color ?? '#D97757' }}" title="{{ $task->assignee->name }}">
                                    {{ $task->assignee->initials ?? strtoupper(substr($task->assignee->name, 0, 2)) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="wr-empty">Drop tasks here</div>
                @endforelse

                <button class="wr-add-btn" onclick="this.blur()">+ Add task</button>
            </div>
        </div>
    @endforeach
</div>

<script>
(function () {
    'use strict';
    var _taskInstances = [];
    var _colInstance   = null;

    function getLivewire() {
        var el = document.querySelector('[wire\\:id]');
        return el ? Livewire.find(el.getAttribute('wire:id')) : null;
    }

    function initKanban() {
        _taskInstances.forEach(function (s) { try { s.destroy(); } catch (e) {} });
        _taskInstances = [];
        if (_colInstance) { try { _colInstance.destroy(); } catch (e) {} _colInstance = null; }

        document.querySelectorAll('[data-status]').forEach(function (col) {
            var inst = Sortable.create(col, {
                group       : 'tasks',
                animation   : 200,
                ghostClass  : 'sortable-ghost',
                chosenClass : 'sortable-chosen',
                dragClass   : 'sortable-drag',
                filter      : '.wr-add-btn, .wr-empty',
                onEnd: function (evt) {
                    if (evt.from === evt.to) return;
                    var taskId    = parseInt(evt.item.getAttribute('data-task-id'), 10);
                    var newStatus = evt.to.getAttribute('data-status');
                    if (!taskId || !newStatus) return;
                    var wc = getLivewire();
                    if (wc) wc.moveTask(taskId, newStatus);
                }
            });
            _taskInstances.push(inst);
        });

        var board = document.getElementById('kanban-board');
        if (board) {
            _colInstance = Sortable.create(board, {
                animation   : 220,
                handle      : '.col-drag-handle',
                ghostClass  : 'wr-col-ghost',
                chosenClass : 'wr-col-chosen',
                dragClass   : 'sortable-drag',
                filter      : '.wr-col-body',
                onEnd: function () {
                    var ids = Array.from(board.querySelectorAll('[data-col-id]'))
                                   .map(function (el) { return parseInt(el.getAttribute('data-col-id'), 10); });
                    var wc = getLivewire();
                    if (wc) wc.reorderColumns(ids);
                }
            });
        }
    }

    window.handleColRename = function (input, columnId) {
        var name = input.value.trim();
        if (!name) return;
        var wc = getLivewire();
        if (wc) wc.renameColumn(columnId, name);
    };

    window.confirmDeleteColumn = function (columnId, label) {
        if (!confirm('Delete column "' + label + '"?\n\nAll tasks will be moved to Backlog.')) return;
        var wc = getLivewire();
        if (wc) wc.deleteColumn(columnId);
    };

    document.addEventListener('DOMContentLoaded', function () { setTimeout(initKanban, 200); });
    document.addEventListener('livewire:updated',  function () { setTimeout(initKanban, 50); });
})();
</script>

</x-filament-panels::page>
