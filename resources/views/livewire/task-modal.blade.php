{{-- Task Detail / New Task Modal — Livewire component --}}
{{-- Root div required by Livewire; modal visibility controlled by @if($open) --}}
<div x-data="{ guideOpen: false, guideMode: 'preview' }"
     x-on:open-task.window="$wire.openTask($event.detail.id)"
     x-on:new-task.window="$wire.newTask()"
     x-on:guide-saved.window="guideMode = 'preview'">
@php
    $priorityMap = [
        'critical' => ['label' => '↑↑ Critical', 'text' => '#b94040', 'bg' => '#fdf0f0'],
        'high'     => ['label' => '↑ High',      'text' => '#b94040', 'bg' => '#fdf0f0'],
        'medium'   => ['label' => '→ Medium',    'text' => '#9a7a1a', 'bg' => '#fef9ec'],
        'low'      => ['label' => '↓ Low',       'text' => '#2e7d55', 'bg' => '#edf7f2'],
    ];
    $statusMap = [
        'todo'        => ['label' => 'To Do',       'text' => '#8c8c8a', 'bg' => '#F5F4EF'],
        'in-progress' => ['label' => 'In Progress', 'text' => '#D97757', 'bg' => '#fdf3ee'],
        'in-review'   => ['label' => 'In Review',   'text' => '#7b5ea7', 'bg' => '#f3f0fb'],
        'done'        => ['label' => 'Done',        'text' => '#2e7d55', 'bg' => '#edf7f2'],
    ];
@endphp

@if($open)
{{-- Overlay --}}
<div class="fixed inset-0 z-40 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.45)"
     wire:click.self="close"
     x-on:keydown.escape.window="$wire.close()">

    {{-- Modal shell: Surface outer + white left panel --}}
    <div class="relative flex w-full max-w-[820px] max-h-[90vh] rounded-2xl overflow-hidden"
         style="background:#F5F4EF; box-shadow: 0 20px 60px rgba(0,0,0,0.18)"
         wire:click.stop>

        {{-- ── LEFT: Content panel ───────────────────────────────────────── --}}
        <div class="flex flex-col flex-1 min-w-0 bg-white overflow-y-auto">

            {{-- Title area --}}
            <div class="px-7 pt-6 pb-4 border-b border-hairline">
                @if(($editingTitle || $isNew) && $canEdit['meta'])
                    <input wire:model="title"
                           wire:blur="saveField('title')"
                           wire:keydown.enter="saveField('title')"
                           autofocus
                           placeholder="Task title…"
                           class="w-full text-[19px] font-semibold text-ink bg-transparent outline-none placeholder:text-muted placeholder:font-normal border-b-2 border-accent pb-1 leading-snug">
                @else
                    <h2 class="text-[19px] font-semibold text-ink leading-snug {{ $canEdit['meta'] ? 'cursor-text hover:text-accent transition-colors' : '' }}"
                        @if($canEdit['meta']) wire:click="$set('editingTitle', true)" @endif>{{ $title }}</h2>
                @endif

                {{-- Breadcrumb: project → task --}}
                @if($task?->project)
                <p class="text-[12px] text-muted mt-1.5 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full inline-block shrink-0" style="background:{{ $task->project->color ?? '#D97757' }}"></span>
                    {{ $task->project->name }}
                    <span class="text-hairline">›</span>
                    <span class="text-dim">#{{ $task->id }}</span>
                </p>
                @endif
            </div>

            {{-- Description --}}
            <div class="px-7 py-5 border-b border-hairline">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Description</p>
                @if($editingDescription && $canEdit['meta'])
                    <textarea wire:model="description"
                              wire:blur="saveField('description')"
                              rows="4"
                              placeholder="Add a description…"
                              class="w-full text-[13px] text-dim leading-relaxed bg-surface border border-line rounded-lg px-3 py-2.5 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted placeholder:italic"></textarea>
                @else
                    <div class="min-h-[44px] {{ $canEdit['meta'] ? 'cursor-text group' : '' }}"
                         @if($canEdit['meta']) wire:click="$set('editingDescription', true)" @endif>
                        @if($description)
                            <p class="text-[13px] text-dim leading-relaxed {{ $canEdit['meta'] ? 'group-hover:bg-surface rounded-lg px-2 py-1.5 -mx-2 transition-colors' : '' }}">{{ $description }}</p>
                        @else
                            <p class="text-[13px] text-muted italic {{ $canEdit['meta'] ? 'group-hover:bg-surface rounded-lg px-2 py-1.5 -mx-2 transition-colors' : '' }}">{{ $canEdit['meta'] ? 'Click to add a description…' : 'No description.' }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Implementation Guide trigger --}}
            @if(! $isNew)
            <div class="px-7 py-3 border-b border-hairline flex items-center gap-2">
                @if($task?->guide)
                <button @click="guideOpen = true; guideMode = 'preview'"
                        class="inline-flex items-center gap-1.5 text-[12px] font-medium text-info-text hover:text-accent transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                    Implementation Guide
                </button>
                @elseif($canEdit['meta'])
                <button @click="guideOpen = true; guideMode = 'edit'"
                        class="inline-flex items-center gap-1.5 text-[12px] font-medium text-muted hover:text-ink transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Add implementation guide
                </button>
                @endif
            </div>
            @endif

            {{-- ── Checklist ────────────────────────────────────────────────────── --}}
            @if($task && (! $isNew))
            <div class="px-7 py-5 border-b border-hairline">
                @php
                    $total   = $task->checklists->count();
                    $checked = $task->checklists->where('is_checked', true)->count();
                    $pct     = $total > 0 ? (int) round($checked / $total * 100) : 0;
                @endphp

                {{-- Header --}}
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Checklist</p>
                    @if($total > 0)
                    <span class="text-[11px] text-muted">{{ $checked }} / {{ $total }}</span>
                    @endif
                </div>

                {{-- Progress bar --}}
                @if($total > 0)
                <div class="w-full h-1 bg-hairline rounded-full mb-3 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300"
                         style="width: {{ $pct }}%; background: #3d9970;"></div>
                </div>
                @endif

                {{-- Items --}}
                @if($total > 0)
                <ul class="space-y-0.5 mb-3">
                    @foreach($task->checklists as $checkItem)
                    <li class="group flex items-center gap-2.5 py-1 rounded-lg hover:bg-canvas px-1.5 -mx-1.5 transition-colors duration-100"
                        wire:key="cl-{{ $checkItem->id }}">

                        {{-- Checkbox --}}
                        <input type="checkbox"
                               @checked($checkItem->is_checked)
                               wire:click="toggleChecklistItem({{ $checkItem->id }})"
                               class="w-4 h-4 rounded flex-shrink-0 cursor-pointer accent-[#3d9970]">

                        {{-- Label (inline editable) --}}
                        <div class="flex-1 min-w-0"
                             x-data="{ editing: false, label: {{ Js::from($checkItem->label) }} }">
                            <span x-show="!editing"
                                  @click="editing = true"
                                  class="block text-[13px] leading-snug cursor-text truncate transition-colors {{ $checkItem->is_checked ? 'line-through text-muted' : 'text-ink' }}">
                                {{ $checkItem->label }}
                            </span>
                            <input x-show="editing"
                                   x-ref="input"
                                   x-model="label"
                                   x-on:vue:show="$nextTick(() => $refs.input.focus())"
                                   x-init="$watch('editing', v => v && $nextTick(() => $refs.input.focus()))"
                                   @blur="editing = false; $wire.renameChecklistItem({{ $checkItem->id }}, label)"
                                   @keydown.enter="editing = false; $wire.renameChecklistItem({{ $checkItem->id }}, label)"
                                   @keydown.escape="editing = false; label = {{ Js::from($checkItem->label) }}"
                                   class="w-full text-[13px] text-ink bg-surface border border-line rounded px-1.5 py-0.5 outline-none focus:border-accent transition-colors">
                        </div>

                        {{-- Delete (hover only) --}}
                        <button wire:click="deleteChecklistItem({{ $checkItem->id }})"
                                class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-100 w-5 h-5 flex items-center justify-center rounded text-muted hover:text-[#b94040] cursor-pointer"
                                title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </li>
                    @endforeach
                </ul>
                @endif

                {{-- Add item — hidden for unclaimed tasks --}}
                @if(! $canClaim)
                <div class="flex items-center gap-2"
                     x-data="{ label: '' }">
                    <input x-model="label"
                           @keydown.enter="if(label.trim()) { $wire.addChecklistItem(label.trim()); label = '' }"
                           type="text"
                           placeholder="Add an item…"
                           class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-1.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted placeholder:italic">
                    <button @click="if(label.trim()) { $wire.addChecklistItem(label.trim()); label = '' }"
                            class="px-3 py-1.5 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors cursor-pointer">
                        Add
                    </button>
                </div>
                @endif
            </div>
            @endif

            {{-- Comments --}}
            <div class="px-7 py-5 flex-1">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-4">Comments</p>

                @if($task)
                <div class="space-y-4 mb-5">
                    @forelse($task->comments as $comment)
                    <div class="flex gap-3" wire:key="comment-{{ $comment->id }}">
                        {{-- Avatar --}}
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-0.5"
                             style="background: {{ $comment->author->color ?? '#D97757' }}">
                            {{ $comment->author->initials ?? strtoupper(substr($comment->author->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2 mb-1">
                                <span class="text-[13px] font-semibold text-ink">{{ $comment->author->name }}</span>
                                <span class="text-[11px] text-muted">{{ $comment->created_at->diffForHumans() }}</span>
                                @if($canEdit['deleteComment'])
                                <button wire:click="deleteComment({{ $comment->id }})"
                                        wire:confirm="Delete this comment?"
                                        class="text-[11px] text-muted hover:text-[#b94040] ml-auto transition-colors cursor-pointer">Delete</button>
                                @endif
                            </div>
                            <p class="text-[13px] text-dim leading-relaxed">{{ $comment->body }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-[13px] text-muted italic">No comments yet.</p>
                    @endforelse
                </div>
                @endif

                {{-- Add comment — hidden for unclaimed tasks --}}
                @if(! $canClaim)
                <div class="flex gap-3 items-start">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-0.5"
                         style="background: {{ auth()->user()->color ?? '#D97757' }}">
                        {{ auth()->user()->initials ?? strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 flex gap-2">
                        <textarea wire:model="commentBody"
                                  wire:keydown.meta.enter="addComment"
                                  rows="1"
                                  placeholder="Write a comment… (⌘↵ to send)"
                                  class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted placeholder:italic"></textarea>
                        <button wire:click="addComment"
                                class="px-3 py-2 bg-accent hover:bg-accent-hover text-white text-[12px] font-medium rounded-lg transition-colors shrink-0 self-start cursor-pointer">Send</button>
                    </div>
                </div>
                @else
                <p class="text-[12px] text-muted italic">Claim this task to leave a comment.</p>
                @endif
            </div>
        </div>

        {{-- ── RIGHT: Meta sidebar ───────────────────────────────────────── --}}
        <div class="w-[240px] shrink-0 flex flex-col border-l border-hairline overflow-y-auto">

            {{-- Close button --}}
            <div class="flex justify-end px-4 pt-4 pb-2">
                <button wire:click="close"
                        class="w-7 h-7 rounded-full flex items-center justify-center text-muted hover:text-ink transition-colors cursor-pointer"
                        style="background: rgba(0,0,0,0.07)"
                        title="Close">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="px-5 pb-6 space-y-0 divide-y divide-hairline">

                {{-- Status --}}
                <div class="py-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1.5">Status</p>
                    @if($canEdit['status'])
                    <select wire:model.live="status"
                            wire:change="saveField('status')"
                            class="w-full text-[12px] font-semibold rounded-md px-2 py-1.5 border border-line appearance-none cursor-pointer outline-none focus:border-accent transition-colors"
                            style="background: {{ $statusMap[$status]['bg'] ?? '#F5F4EF' }}; color: {{ $statusMap[$status]['text'] ?? '#8c8c8a' }}">
                        @foreach($columns as $col)
                        <option value="{{ $col->slug }}" @selected($status === $col->slug)>{{ $col->name }}</option>
                        @endforeach
                    </select>
                    @else
                    <span class="inline-flex items-center text-[11px] font-semibold rounded-[5px] px-2 py-0.5"
                          style="background: {{ $statusMap[$status]['bg'] ?? '#F5F4EF' }}; color: {{ $statusMap[$status]['text'] ?? '#8c8c8a' }}">
                        {{ $statusMap[$status]['label'] ?? $status }}
                    </span>
                    @endif
                </div>

                {{-- Priority --}}
                <div class="py-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1.5">Priority</p>
                    @if($canEdit['priority'])
                    <select wire:model.live="priority"
                            wire:change="saveField('priority')"
                            class="w-full text-[12px] font-semibold rounded-md px-2 py-1.5 border border-line appearance-none cursor-pointer outline-none focus:border-accent transition-colors"
                            style="background: {{ $priorityMap[$priority]['bg'] ?? '#F5F4EF' }}; color: {{ $priorityMap[$priority]['text'] ?? '#8c8c8a' }}">
                        <option value="critical" @selected($priority==='critical')>↑↑ Critical</option>
                        <option value="high"     @selected($priority==='high')>↑ High</option>
                        <option value="medium"   @selected($priority==='medium')>→ Medium</option>
                        <option value="low"      @selected($priority==='low')>↓ Low</option>
                    </select>
                    @else
                    <span class="inline-flex items-center text-[11px] font-semibold rounded-[5px] px-2 py-0.5"
                          style="background: {{ $priorityMap[$priority]['bg'] ?? '#fef9ec' }}; color: {{ $priorityMap[$priority]['text'] ?? '#9a7a1a' }}">
                        {{ $priorityMap[$priority]['label'] ?? $priority }}
                    </span>
                    @endif
                </div>

                {{-- Project --}}
                <div class="py-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1.5">Project</p>
                    @if($canEdit['project'])
                    <select wire:model.live="projectId"
                            wire:change="saveField('projectId')"
                            class="w-full text-[12px] font-medium text-ink rounded-md px-2 py-1.5 bg-surface border border-line appearance-none cursor-pointer outline-none focus:border-accent transition-colors">
                        <option value="">No project</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected($projectId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @else
                    <p class="text-[12px] text-ink">
                        {{ $task?->project?->name ?? '—' }}
                    </p>
                    @endif
                </div>

                {{-- Assignee --}}
                <div class="py-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1.5">Assignee</p>
                    @if($canEdit['assignee'])
                    <select wire:model.live="assignedTo"
                            wire:change="saveField('assignedTo')"
                            class="w-full text-[12px] font-medium text-ink rounded-md px-2 py-1.5 bg-surface border border-line appearance-none cursor-pointer outline-none focus:border-accent transition-colors">
                        <option value="">Unassigned</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected($assignedTo == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @endif
                    @if($task?->assignee)
                    <div class="flex items-center gap-2 mt-2">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white shrink-0"
                             style="background: {{ $task->assignee->color ?? '#D97757' }}">
                            {{ $task->assignee->initials ?? strtoupper(substr($task->assignee->name, 0, 1)) }}
                        </div>
                        <span class="text-[12px] text-dim">{{ $task->assignee->name }}</span>
                    </div>
                    @else
                    <p class="text-[12px] text-muted">Unassigned</p>
                    @endif
                </div>

                {{-- Due date --}}
                <div class="py-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1.5">Due Date</p>
                    @if($canEdit['dates'])
                    <input type="date"
                           wire:model.lazy="dueDate"
                           wire:change="saveField('dueDate')"
                           class="w-full text-[12px] text-ink rounded-md px-2 py-1.5 bg-surface border border-line outline-none focus:border-accent transition-colors cursor-pointer">
                    @else
                    <p class="text-[12px] text-ink">{{ $dueDate ? \Carbon\Carbon::parse($dueDate)->format('M j, Y') : '—' }}</p>
                    @endif
                    @if($task?->isOverdue())
                    <p class="text-[11px] font-semibold mt-1" style="color:#b94040">⚠ Overdue</p>
                    @endif
                </div>

                {{-- Estimated hours --}}
                <div class="py-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1.5">Est. Hours</p>
                    @if($canEdit['dates'])
                    <input type="number"
                           wire:model.lazy="estimatedHours"
                           wire:change="saveField('estimatedHours')"
                           min="0" max="999" step="0.5"
                           placeholder="—"
                           class="w-full text-[12px] text-ink rounded-md px-2 py-1.5 bg-surface border border-line outline-none focus:border-accent transition-colors placeholder:text-muted">
                    @else
                    <p class="text-[12px] text-ink">{{ $estimatedHours ? $estimatedHours . 'h' : '—' }}</p>
                    @endif
                </div>

                {{-- Created at --}}
                @if($task)
                <div class="py-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Created</p>
                    <p class="text-[12px] text-dim">{{ $task->created_at->format('M j, Y') }}</p>
                    <p class="text-[11px] text-muted">{{ $task->created_at->diffForHumans() }}</p>
                </div>
                @endif
            </div>

            {{-- Save button for new tasks --}}
            @if($isNew)
            <div class="px-5 pb-5">
                <button wire:click="saveNew"
                        class="w-full py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-medium rounded-lg transition-colors cursor-pointer">
                    Create Task
                </button>
            </div>
            @elseif($canClaim)
            <div class="px-5 pb-5 pt-4 border-t border-hairline">
                <button wire:click="claimTask"
                        class="w-full py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-medium rounded-lg transition-colors cursor-pointer inline-flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59"/>
                    </svg>
                    Claim this task
                </button>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ── Guide Drawer (teleported outside modal overflow) ──────────────── --}}
@if($task && ! $isNew)
<template x-teleport="body">
    {{-- Backdrop (only dims the area behind the drawer, above the modal) --}}
    <div x-show="guideOpen"
         x-cloak
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="guideOpen = false"
         class="fixed inset-0 z-[60] bg-black/20"></div>

    {{-- Drawer panel --}}
    <div x-show="guideOpen"
         x-cloak
         x-transition:enter="transition-transform duration-250 ease-out"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[61] flex flex-col bg-white border-l border-line shadow-[0_0_60px_rgba(0,0,0,0.18)]"
         style="width: 480px">

        {{-- Drawer header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-hairline shrink-0">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/>
                </svg>
                <span class="text-[14px] font-semibold text-ink">Implementation Guide</span>
            </div>
            <div class="flex items-center gap-2">
                {{-- Edit / Preview toggle — only for editors --}}
                @if($canEdit['meta'])
                <div class="flex rounded-lg border border-line overflow-hidden text-[11px] font-medium">
                    <button @click="guideMode = 'preview'"
                            :class="guideMode === 'preview' ? 'bg-surface text-ink' : 'text-muted hover:bg-canvas'"
                            class="px-3 py-1 cursor-pointer transition-colors">Preview</button>
                    <button @click="guideMode = 'edit'"
                            :class="guideMode === 'edit' ? 'bg-surface text-ink' : 'text-muted hover:bg-canvas'"
                            class="px-3 py-1 cursor-pointer transition-colors border-l border-line">Edit</button>
                </div>
                @endif
                <button @click="guideOpen = false"
                        class="w-7 h-7 rounded-full flex items-center justify-center text-muted hover:text-ink transition-colors cursor-pointer"
                        style="background: rgba(0,0,0,0.07)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        {{-- Preview mode --}}
        <div x-show="guideMode === 'preview'" class="flex-1 overflow-y-auto px-6 py-5">
            @if($task->guide)
            <div class="guide-content">{!! Str::markdown($task->guide, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
            @elseif($canEdit['meta'])
            <div class="flex flex-col items-center justify-center h-full text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-hairline mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <p class="text-[13px] text-muted mb-3">No guide yet.</p>
                <button @click="guideMode = 'edit'"
                        class="text-[12px] font-medium text-accent hover:underline cursor-pointer">
                    Write one →
                </button>
            </div>
            @else
            <p class="text-[13px] text-muted italic">No guide available.</p>
            @endif
        </div>

        {{-- Edit mode --}}
        @if($canEdit['meta'])
        <div x-show="guideMode === 'edit'" class="flex flex-col flex-1 overflow-hidden">
            <div class="px-6 pt-5 pb-2 shrink-0">
                <p class="text-[11px] text-muted mb-2">Supports <span class="font-medium text-ink">Markdown</span> — headings, lists, code blocks, bold/italic.</p>
            </div>
            <textarea wire:model="guide"
                      rows="20"
                      placeholder="Write the implementation guide in Markdown…"
                      class="flex-1 mx-6 mb-4 text-[13px] font-mono text-ink bg-surface border border-line rounded-lg px-4 py-3 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted placeholder:not-italic"></textarea>
            <div class="px-6 pb-5 shrink-0 flex gap-2">
                <button wire:click="saveGuide"
                        class="flex-1 py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-medium rounded-lg transition-colors cursor-pointer">
                    Save Guide
                </button>
                <button @click="guideMode = 'preview'"
                        class="px-4 py-2 border border-line rounded-lg text-[13px] text-dim hover:bg-surface transition-colors cursor-pointer">
                    Cancel
                </button>
            </div>
        </div>
        @endif

    </div>
</template>
@endif

@endif
</div>{{-- /Livewire root --}}
