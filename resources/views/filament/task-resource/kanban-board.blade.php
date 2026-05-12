<x-filament-panels::page>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<style>
    /* Column body flex layout — margin-based gaps survive Sortable DOM rewrites */
    .kanban-col-body  { display: flex; flex-direction: column; }
    .task-card        { margin-bottom: 10px; }
    .task-card:last-child { margin-bottom: 0; }

    /* Card drag feedback */
    .sortable-ghost-card   { opacity: 0.3; }
    .sortable-chosen-card  { box-shadow: 0 10px 30px rgba(0,0,0,.22); transform: rotate(1.5deg) scale(1.02); }
    .sortable-drag-card    { opacity: 0; }

    /* Column drag feedback */
    .sortable-ghost-col    { opacity: 0.4; }
    .sortable-chosen-col   { box-shadow: 0 12px 32px rgba(0,0,0,.25); transform: rotate(-1deg) scale(1.01); }
    .sortable-drag-col     { opacity: 0; }

    /* Inline column name editing */
    .col-name-input { background: transparent; border: none; outline: none; color: white;
                      font-weight: 700; font-size: .875rem; text-transform: uppercase;
                      letter-spacing: .1em; width: 100%; cursor: text; }
    .col-name-input:focus { border-bottom: 1px dashed rgba(255,255,255,.5); }
</style>

@php
    /* ── Colour palette ─────────────────────────────────────────────────────── */
    $palette = [
        'slate'   => ['header' => 'bg-slate-800',    'body' => 'bg-slate-50 dark:bg-slate-900/40',     'border' => 'border-slate-300 dark:border-slate-700',   'count' => 'bg-slate-700 text-slate-200'],
        'blue'    => ['header' => 'bg-blue-700',     'body' => 'bg-blue-50 dark:bg-blue-900/20',       'border' => 'border-blue-300 dark:border-blue-700',     'count' => 'bg-blue-600 text-blue-100'],
        'amber'   => ['header' => 'bg-amber-600',    'body' => 'bg-amber-50 dark:bg-amber-900/20',     'border' => 'border-amber-300 dark:border-amber-700',   'count' => 'bg-amber-500 text-amber-100'],
        'emerald' => ['header' => 'bg-emerald-700',  'body' => 'bg-emerald-50 dark:bg-emerald-900/20', 'border' => 'border-emerald-300 dark:border-emerald-800','count' => 'bg-emerald-600 text-emerald-100'],
        'purple'  => ['header' => 'bg-purple-700',   'body' => 'bg-purple-50 dark:bg-purple-900/20',   'border' => 'border-purple-300 dark:border-purple-700', 'count' => 'bg-purple-600 text-purple-100'],
        'rose'    => ['header' => 'bg-rose-600',     'body' => 'bg-rose-50 dark:bg-rose-900/20',       'border' => 'border-rose-300 dark:border-rose-700',     'count' => 'bg-rose-500 text-rose-100'],
        'cyan'    => ['header' => 'bg-cyan-700',     'body' => 'bg-cyan-50 dark:bg-cyan-900/20',       'border' => 'border-cyan-300 dark:border-cyan-700',     'count' => 'bg-cyan-600 text-cyan-100'],
        'indigo'  => ['header' => 'bg-indigo-700',   'body' => 'bg-indigo-50 dark:bg-indigo-900/20',   'border' => 'border-indigo-300 dark:border-indigo-700', 'count' => 'bg-indigo-600 text-indigo-100'],
        'orange'  => ['header' => 'bg-orange-600',   'body' => 'bg-orange-50 dark:bg-orange-900/20',   'border' => 'border-orange-300 dark:border-orange-700', 'count' => 'bg-orange-500 text-orange-100'],
        'teal'    => ['header' => 'bg-teal-700',     'body' => 'bg-teal-50 dark:bg-teal-900/20',       'border' => 'border-teal-300 dark:border-teal-700',     'count' => 'bg-teal-600 text-teal-100'],
    ];

    $priorityLeft  = ['high' => 'border-l-red-500',  'medium' => 'border-l-amber-400', 'low' => 'border-l-slate-300'];
    $priorityBadge = ['high'   => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                      'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                      'low'    => 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'];
    $typeBadge     = ['bug'     => 'bg-red-50 text-red-600 ring-1 ring-red-200 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-800',
                      'feature' => 'bg-blue-50 text-blue-600 ring-1 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-800',
                      'change'  => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600'];
@endphp

{{-- ─────────────── Filter bar ─────────────── --}}
<div class="flex flex-wrap items-center gap-3 mb-5 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-2 flex-1 min-w-[180px]">
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M7 12h10M10 17h4"/>
        </svg>
        <select wire:model.live="projectFilter"
            class="w-full rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm py-1.5 focus:ring-2 focus:ring-primary-500">
            <option value="">All Projects</option>
            @foreach ($this->projects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4l9 9v7l3-3v-4l9-9H3z"/>
        </svg>
        <select wire:model.live="priorityFilter"
            class="rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm py-1.5 focus:ring-2 focus:ring-primary-500">
            <option value="">All Priorities</option>
            <option value="high">🔴 High</option>
            <option value="medium">🟡 Medium</option>
            <option value="low">⚪ Low</option>
        </select>
    </div>
    <span class="text-xs text-gray-400 dark:text-gray-500 font-medium shrink-0">
        @php $total = collect($this->columns)->sum(fn($c) => count($c['tasks'])) @endphp
        {{ $total }} {{ Str::plural('task', $total) }}
    </span>
    <span class="text-xs text-gray-300 dark:text-gray-600 shrink-0 hidden md:block">
        · drag column headers to reorder · click name to rename
    </span>
</div>

{{-- ─────────────── Board ─────────────── --}}
{{-- Column wrapper is itself sortable — id="kanban-board" targeted by columnSortable --}}
<div id="kanban-board" class="flex gap-4 items-start overflow-x-auto pb-4">
    @foreach ($this->columns as $column)
        @php $s = $palette[$column['color']] ?? $palette['slate'] @endphp

        {{-- data-col-id used by reorderColumns() --}}
        <div class="kanban-column shrink-0 w-72 rounded-xl overflow-hidden shadow border {{ $s['border'] }}"
             data-col-id="{{ $column['id'] }}">

            {{-- ── Column header (drag handle for column reorder) ── --}}
            <div class="{{ $s['header'] }} col-drag-handle px-3 py-2.5 flex items-center gap-2 cursor-grab active:cursor-grabbing select-none">
                <span class="text-base leading-none shrink-0">{{ $column['icon'] }}</span>

                {{-- Inline-editable name --}}
                <input
                    class="col-name-input flex-1 min-w-0"
                    value="{{ $column['label'] }}"
                    title="Click to rename"
                    onblur="handleColRename(this, {{ $column['id'] }})"
                    onkeydown="if(event.key==='Enter'||event.key==='Escape'){this.blur()}"
                    onclick="event.stopPropagation()"
                >

                <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0 {{ $s['count'] }}">
                    {{ count($column['tasks']) }}
                </span>

                {{-- Delete button (hidden for first column / backlog safety) --}}
                <button
                    onclick="event.stopPropagation(); confirmDeleteColumn({{ $column['id'] }}, '{{ addslashes($column['label']) }}')"
                    class="shrink-0 text-white/50 hover:text-white/90 transition-colors ml-1"
                    title="Delete column"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- ── Drop zone for task cards ── --}}
            <div class="kanban-col-body p-3 min-h-72 {{ $s['body'] }}"
                 data-status="{{ $column['key'] }}">

                @forelse ($column['tasks'] as $task)
                    @php
                        $overdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'done';
                        $lBrd    = $priorityLeft[$task->priority]  ?? 'border-l-slate-300';
                        $pBdg    = $priorityBadge[$task->priority] ?? '';
                        $tBdg    = $typeBadge[$task->type]         ?? '';
                    @endphp

                    <div class="task-card bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 border-l-4 {{ $lBrd }} shadow-sm hover:shadow-md cursor-grab active:cursor-grabbing select-none transition-shadow duration-150"
                         data-task-id="{{ $task->id }}">
                        <div class="p-3">

                            {{-- Badges --}}
                            <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                                @if ($task->type)
                                    <span class="inline-block text-xs px-1.5 py-0.5 rounded font-medium {{ $tBdg }}">{{ ucfirst($task->type) }}</span>
                                @endif
                                <span class="inline-block text-xs px-1.5 py-0.5 rounded font-medium {{ $pBdg }}">{{ ucfirst($task->priority) }}</span>
                            </div>

                            {{-- Title --}}
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-snug mb-1 line-clamp-2">
                                <a href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $task->id]) }}"
                                   class="hover:text-primary-600 dark:hover:text-primary-400"
                                   onclick="event.stopPropagation()">{{ $task->title }}</a>
                            </p>

                            {{-- Project --}}
                            @if ($task->project)
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate mb-2">
                                    📁 {{ $task->project->name }}
                                </p>
                            @endif

                            {{-- Footer meta --}}
                            <div class="flex items-center gap-2 flex-wrap text-xs text-gray-400 dark:text-gray-500 pt-2 mt-1 border-t border-gray-100 dark:border-gray-700">
                                @if ($task->due_date)
                                    <span class="{{ $overdue ? 'text-red-500 dark:text-red-400 font-semibold' : '' }}">
                                        {{ $overdue ? '⚠' : '📅' }} {{ $task->due_date->format('M j') }}
                                    </span>
                                @endif
                                @if ($task->estimated_hours)
                                    <span>⏱ {{ $task->estimated_hours }}h</span>
                                @endif
                                @if ($task->assignee)
                                    <span class="ml-auto bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded-full font-medium max-w-[90px] truncate"
                                          title="{{ $task->assignee->name }}">{{ $task->assignee->name }}</span>
                                @endif
                            </div>

                            {{-- Labels --}}
                            @if ($task->labels)
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach ($task->labels as $label)
                                        <span class="text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded ring-1 ring-indigo-200 dark:ring-indigo-700">{{ $label }}</span>
                                    @endforeach
                                </div>
                            @endif

                        </div>
                    </div>

                @empty
                    <div class="flex flex-col items-center justify-center h-40 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 text-gray-300 dark:text-gray-700">
                        <svg class="w-7 h-7 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="text-xs">Drop tasks here</span>
                    </div>
                @endforelse

            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────── Scripts ─────────────── --}}
<script>
(function () {
    'use strict';

    var _taskInstances   = [];   // one per column drop zone
    var _colInstance     = null; // one for the board itself

    // ── Helpers ──────────────────────────────────────────────────────────────

    function getLivewire() {
        var el = document.querySelector('[wire\\:id]');
        return el ? Livewire.find(el.getAttribute('wire:id')) : null;
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function initKanban() {
        /* Destroy stale instances */
        _taskInstances.forEach(function (s) { try { s.destroy(); } catch (e) {} });
        _taskInstances = [];
        if (_colInstance) { try { _colInstance.destroy(); } catch (e) {} _colInstance = null; }

        /* 1. Task card drag — one instance per drop zone */
        document.querySelectorAll('[data-status]').forEach(function (col) {
            var inst = Sortable.create(col, {
                group        : 'tasks',
                animation    : 200,
                ghostClass   : 'sortable-ghost-card',
                chosenClass  : 'sortable-chosen-card',
                dragClass    : 'sortable-drag-card',
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

        /* 2. Column drag — children of #kanban-board */
        var board = document.getElementById('kanban-board');
        if (board) {
            _colInstance = Sortable.create(board, {
                animation   : 220,
                handle      : '.col-drag-handle',
                ghostClass  : 'sortable-ghost-col',
                chosenClass : 'sortable-chosen-col',
                dragClass   : 'sortable-drag-col',
                onEnd: function (evt) {
                    /* Collect ordered column IDs after the drag */
                    var ids = Array.from(board.querySelectorAll('[data-col-id]'))
                                   .map(function (el) { return parseInt(el.getAttribute('data-col-id'), 10); });
                    var wc = getLivewire();
                    if (wc) wc.reorderColumns(ids);
                }
            });
        }
    }

    /* ── Inline column rename ──────────────────────────────────────────────── */
    window.handleColRename = function (input, columnId) {
        var name = input.value.trim();
        if (!name) return;
        /* Optimistic UI — nothing to do; Livewire will re-render after call */
        var wc = getLivewire();
        if (wc) wc.renameColumn(columnId, name);
    };

    /* ── Column delete with native confirm ────────────────────────────────── */
    window.confirmDeleteColumn = function (columnId, label) {
        if (!confirm('Delete column "' + label + '"?\n\nAll tasks in this column will be moved to Backlog.')) return;
        var wc = getLivewire();
        if (wc) wc.deleteColumn(columnId);
    };

    /* ── Boot ──────────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () { setTimeout(initKanban, 200); });
    document.addEventListener('livewire:updated',  function () { setTimeout(initKanban,  50); });
})();
</script>

</x-filament-panels::page>
