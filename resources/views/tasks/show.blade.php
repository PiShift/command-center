<x-layouts.app :title="$task->title">

<div class="max-w-3xl mx-auto space-y-5">
    @php
        $canViewBilling = auth()->user()->hasPermission('invoices.view');
        $canManageBilling = auth()->user()->hasPermission('invoices.manage');
        $billingInvoice = $task->activeInvoiceItem?->invoice;
    @endphp

    <div class="flex items-center gap-3 text-[13px] mb-2">
        <a href="{{ route('tasks.index') }}" class="text-muted hover:text-ink">Tasks</a>
        <span class="text-muted">/</span>
        <span class="text-ink truncate">{{ $task->title }}</span>
    </div>

    @include('components.flash')

    <div class="bg-white border border-line rounded-xl p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-[18px] font-bold text-ink mb-2">{{ $task->title }}</h1>
                <div class="flex items-center flex-wrap gap-2">
                    @include('components.badge', ['type' => 'status',   'value' => $task->status])
                    @include('components.badge', ['type' => 'priority', 'value' => $task->priority])
                    @include('components.badge', ['type' => 'type',     'value' => $task->type])
                    @include('components.badge', ['type' => 'source',   'value' => $task->source])
                    @if($canViewBilling)
                        @if($billingInvoice)
                            <a href="{{ route('invoices.show', $billingInvoice) }}" class="hover:opacity-90 transition-opacity">
                                @include('components.badge', ['type' => 'invoice_status', 'value' => $task->invoiceStatus])
                            </a>
                        @else
                            @include('components.badge', ['type' => 'invoice_status', 'value' => $task->invoiceStatus])
                        @endif
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <form method="POST" action="{{ route('tasks.advance', $task) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium border border-line rounded-lg text-dim hover:bg-hairline">
                        {{ match($task->status) { 'backlog' => 'Start', 'in-progress' => 'Mark Done', default => 'Re-open' } }}
                    </button>
                </form>
                @if(auth()->user()->hasPermission('tasks.edit_any'))
                <a href="{{ route('tasks.edit', $task) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-[12px] border border-line rounded-lg text-dim hover:bg-hairline">
                    @include('components.icon', ['name' => 'pencil'])
                    Edit
                </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-x-8 gap-y-3 mt-6 text-[13px] border-t border-hairline pt-5">
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Project</p>
                @if($task->project)
                    <a href="{{ route('projects.show', $task->project) }}" class="text-accent hover:underline">{{ $task->project->name }}</a>
                @else
                    <span class="text-dim">—</span>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Assignee</p>
                @if($task->assignee)
                    <p class="text-ink">{{ $task->assignee->name }}</p>
                @elseif(\Illuminate\Support\Facades\Gate::allows('claim', $task))
                    <form method="POST" action="{{ route('tasks.claim', $task) }}" class="mt-1">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-semibold rounded-lg border cursor-pointer transition-colors duration-150"
                                style="color:#2e7d55;background:#edf7f2;border-color:#b7e0ca"
                                onmouseover="this.style.background='#d6f0e4'"
                                onmouseout="this.style.background='#edf7f2'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59"/>
                            </svg>
                            Take this task
                        </button>
                    </form>
                @else
                    <p class="text-ink">—</p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Agent</p>
                @if($task->agent)
                    <div class="flex items-center gap-2">
                        <x-agent-avatar :agent="$task->agent" size="6" />
                        <div class="flex items-center gap-1.5">
                            <p class="text-ink">{{ $task->agent->name }}</p>
                            <x-provider-icon :provider="$task->agent->runtime?->provider ?? 'default'" size="4" />
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-hairline text-dim mt-1">{{ $task->agent->status }}</span>
                    @if($task->latestQueue)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-surface text-dim mt-1">Queue: {{ $task->latestQueue->status }}</span>
                    @endif
                @else
                    <p class="text-dim">No agent assigned <a href="{{ route('tasks.edit', $task) }}" class="text-accent hover:underline">Assign one</a></p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Due Date</p>
                <p class="{{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-ink' }}">
                    {{ $task->due_date?->format('M d, Y') ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Estimated</p>
                <p class="text-ink">{{ $task->estimated_hours ? $task->estimated_hours . 'h' : '—' }}</p>
            </div>
            @if($task->labels)
            <div class="col-span-2">
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-1">Labels</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($task->labels as $label)
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-hairline text-dim">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        @if($canManageBilling)
        <div class="mt-5 border-t border-hairline pt-4">
            <div class="flex items-center justify-between gap-4 mb-3">
                <div>
                    <p class="text-[11px] font-medium text-muted uppercase tracking-wider">Manual Billing Override</p>
                    <p class="text-[13px] text-dim mt-1">Backfill historical invoiced or paid work without linking a new invoice line item.</p>
                </div>
                @if($task->invoiceOverride)
                <form method="POST" action="{{ route('task-invoice-overrides.destroy', $task) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-[12px] border border-line rounded-lg text-dim hover:bg-hairline">
                        Remove override
                    </button>
                </form>
                @endif
            </div>

            @if($task->invoiceOverride)
            <p class="text-[12px] text-muted mb-3">
                Currently marked as <span class="font-medium text-ink">{{ ucfirst($task->invoiceOverride->status) }}</span>
                @if($task->invoiceOverride->markedBy)
                    by {{ $task->invoiceOverride->markedBy->name }}
                @endif
                @if($task->invoiceOverride->marked_at)
                    on {{ $task->invoiceOverride->marked_at->format('M d, Y H:i') }}
                @endif
            </p>
            @endif

            <form method="POST" action="{{ route('task-invoice-overrides.store', $task) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-[11px] font-medium text-muted uppercase tracking-wider mb-1 block">Status</label>
                        <select name="status"
                                class="w-full rounded-lg border border-line bg-white px-3 py-2 text-[13px] text-ink">
                            <option value="invoiced" @selected(old('status', $task->invoiceOverride?->status ?? 'invoiced') === 'invoiced')>Invoiced</option>
                            <option value="paid" @selected(old('status', $task->invoiceOverride?->status) === 'paid')>Paid</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-[11px] font-medium text-muted uppercase tracking-wider mb-1 block">Note</label>
                    <textarea name="note" rows="3"
                              class="w-full rounded-lg border border-line bg-white px-3 py-2 text-[13px] text-ink"
                              placeholder="Optional context for this manual override">{{ old('note', $task->invoiceOverride?->note) }}</textarea>
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium border border-line rounded-lg text-dim hover:bg-hairline">
                    Mark as invoiced
                </button>
            </form>
        </div>
        @endif

        @if($task->description)
        <div class="mt-5 border-t border-hairline pt-4">
            <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-2">Description</p>
            <p class="text-[13px] text-ink whitespace-pre-line">{{ $task->description }}</p>
        </div>
        @endif

        {{-- ── Implementation Guide ──────────────────────────────────────────── --}}
        @php $canEditTask = auth()->user()->hasPermission('tasks.edit_any'); @endphp
        @if($task->guide || $canEditTask)
        <div class="mt-5 border-t border-hairline pt-4"
             x-data="{ editing: false, guide: {{ Js::from($task->guide ?? '') }} }">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                    <p class="text-[11px] font-medium text-muted uppercase tracking-wider">Implementation Guide</p>
                </div>
                @if($canEditTask)
                <button x-show="!editing" @click="editing = true"
                        class="text-[12px] font-medium text-muted hover:text-ink transition-colors cursor-pointer">
                    {{ $task->guide ? 'Edit' : 'Add guide' }}
                </button>
                @endif
            </div>

            {{-- Preview --}}
            <div x-show="!editing">
                @if($task->guide)
                <div class="guide-content">{!! Str::markdown($task->guide, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                @else
                <p class="text-[13px] text-muted italic">No guide yet.</p>
                @endif
            </div>

            {{-- Inline edit --}}
            @if($canEditTask)
            <div x-show="editing" x-cloak>
                <form method="POST" action="{{ route('tasks.update', $task) }}">
                    @csrf @method('PATCH')
                    {{-- Pass all required fields so validation passes --}}
                    <input type="hidden" name="title" value="{{ $task->title }}">
                    <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                    <input type="hidden" name="type" value="{{ $task->type }}">
                    <input type="hidden" name="priority" value="{{ $task->priority }}">
                    <input type="hidden" name="status" value="{{ $task->status }}">
                    <input type="hidden" name="source" value="{{ $task->source }}">
                    <textarea name="guide"
                              x-model="guide"
                              rows="14"
                              placeholder="Write the implementation guide in Markdown…"
                              class="w-full text-[13px] font-mono text-ink bg-surface border border-line rounded-lg px-4 py-3 outline-none focus:border-accent focus:bg-white transition-colors resize-y placeholder:text-muted mb-3"></textarea>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="px-4 py-1.5 bg-accent hover:bg-accent-hover text-white text-[12px] font-medium rounded-lg transition-colors cursor-pointer">
                            Save
                        </button>
                        <button type="button" @click="editing = false; guide = {{ Js::from($task->guide ?? '') }}"
                                class="px-4 py-1.5 border border-line rounded-lg text-[12px] text-dim hover:bg-surface transition-colors cursor-pointer">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Checklist ─────────────────────────────────────────────────────── --}}
        @php
            $checklists = $task->checklists;
            $total      = $checklists->count();
            $checked    = $checklists->where('is_checked', true)->count();
            $pct        = $total > 0 ? (int) round($checked / $total * 100) : 0;
        @endphp
        <div class="mt-5 border-t border-hairline pt-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium text-muted uppercase tracking-wider">Checklist</p>
                @if($total > 0)
                <span class="text-[11px] text-muted">{{ $checked }} / {{ $total }}</span>
                @endif
            </div>

            @if($total > 0)
            <div class="w-full h-1 bg-hairline rounded-full mb-3 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: #3d9970;"></div>
            </div>
            <ul class="space-y-0.5 mb-4">
                @foreach($checklists as $checkItem)
                <li class="group flex items-center gap-2.5 py-1 rounded-lg hover:bg-canvas px-1.5 -mx-1.5 transition-colors duration-100">
                    <form method="POST" action="{{ route('checklists.update', [$task, $checkItem]) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="is_checked" value="{{ $checkItem->is_checked ? '0' : '1' }}">
                        <button type="submit"
                                class="w-4 h-4 rounded flex-shrink-0 cursor-pointer accent-[#3d9970] flex items-center justify-center border transition-colors {{ $checkItem->is_checked ? 'bg-[#3d9970] border-[#3d9970]' : 'border-line hover:border-muted' }}">
                            @if($checkItem->is_checked)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            @endif
                        </button>
                    </form>
                    <span class="flex-1 text-[13px] leading-snug {{ $checkItem->is_checked ? 'line-through text-muted' : 'text-ink' }} truncate">
                        {{ $checkItem->label }}
                    </span>
                    <form method="POST" action="{{ route('checklists.destroy', [$task, $checkItem]) }}"
                          class="opacity-0 group-hover:opacity-100 transition-opacity">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-5 h-5 flex items-center justify-center rounded text-muted hover:text-[#b94040] cursor-pointer" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
            @endif

            {{-- Add item --}}
            <form method="POST" action="{{ route('checklists.store', $task) }}" class="flex items-center gap-2">
                @csrf
                <input type="text"
                       name="label"
                       placeholder="Add an item…"
                       autocomplete="off"
                       class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-1.5 outline-none focus:border-accent focus:bg-white transition-colors placeholder:text-muted placeholder:italic">
                <button type="submit"
                        class="px-3 py-1.5 text-[12px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors cursor-pointer">
                    Add
                </button>
            </form>
        </div>

        @if($task->source === 'ai-chat' && $task->original_input)
        <div class="mt-5 border-t border-hairline pt-4">
            <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-2">Original AI Input</p>
            <p class="text-[13px] text-dim whitespace-pre-line bg-hairline rounded-lg p-3">{{ $task->original_input }}</p>
        </div>
        @endif

        {{-- ── Attachments ────────────────────────────────────────────────────── --}}
        @php
            $allMedia   = $task->getMedia('attachments')->merge($task->getMedia('images'));
            $mediaCount = $allMedia->count();
            $canUpload  = auth()->user()->hasPermission('tasks.edit_own') || auth()->user()->hasPermission('tasks.edit_any');
        @endphp
        <div class="mt-5 border-t border-hairline pt-4"
             x-data="{ attachError: '', lightboxSrc: null }">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <p class="text-[11px] font-medium text-muted uppercase tracking-wider">Attachments</p>
                    @if($mediaCount > 0)
                    <span class="text-[11px] font-semibold text-muted bg-surface border border-hairline rounded-full px-2 py-0.5">{{ $mediaCount }}</span>
                    @endif
                </div>
                @if($canUpload)
                <form method="POST" action="{{ route('attachments.store', $task) }}"
                      enctype="multipart/form-data" id="attach-form">
                    @csrf
                    <label class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-medium text-dim bg-surface border border-line rounded-lg hover:bg-hairline hover:text-ink transition-colors duration-150 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                        </svg>
                        Upload
                        <input type="file" name="file" form="attach-form" class="sr-only"
                               @change="document.getElementById('attach-form').submit()">
                    </label>
                </form>
                @endif
            </div>

            @if($mediaCount === 0)
            <p class="text-[13px] text-muted italic">No attachments yet.</p>
            @else
            <ul class="space-y-1.5">
                @foreach($allMedia as $m)
                @php
                    $isImg    = str_starts_with($m->mime_type, 'image/');
                    $isPdf    = $m->mime_type === 'application/pdf';
                    $dlUrl    = route('attachments.download', ['task' => $task->id, 'media' => $m->id]);
                    $thumbUrl = $isImg ? route('attachments.download', ['task' => $task->id, 'media' => $m->id, 'thumb' => 1]) : null;
                    $sizeFmt  = $m->size >= 1048576 ? round($m->size/1048576, 1).'MB' : round($m->size/1024, 1).'KB';
                @endphp
                <li class="group flex items-center gap-3 py-2 px-2 rounded-lg hover:bg-canvas transition-colors duration-100">
                    <div class="w-10 h-10 shrink-0 rounded-md overflow-hidden flex items-center justify-center bg-surface border border-hairline">
                        @if($isImg && $thumbUrl)
                        <img src="{{ $thumbUrl }}" alt="{{ $m->file_name }}"
                             class="w-full h-full object-cover cursor-zoom-in"
                             @click="lightboxSrc = '{{ $dlUrl }}'">
                        @elseif($isPdf)
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#b94040]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                        </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-ink truncate" title="{{ $m->file_name }}">{{ $m->file_name }}</p>
                        <p class="text-[11px] text-muted">{{ $sizeFmt }} · {{ $m->created_at->format('M j, Y') }}</p>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="{{ $dlUrl }}" download="{{ $m->file_name }}"
                           class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-ink transition-colors"
                           style="background: rgba(0,0,0,0.07)" title="Download">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                        </a>
                        @if($canUpload)
                        <form method="POST" action="{{ route('attachments.destroy', ['task' => $task->id, 'media' => $m->id]) }}"
                              onsubmit="return confirm('Remove this attachment?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-7 h-7 flex items-center justify-center rounded-full text-muted hover:text-[#b94040] transition-colors cursor-pointer"
                                    style="background: rgba(0,0,0,0.07)" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
            @endif

            {{-- Lightbox --}}
            <div x-show="lightboxSrc"
                 x-cloak
                 x-transition:enter="transition-opacity duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 @click.self="lightboxSrc = null"
                 @keydown.escape.window="lightboxSrc = null"
                 class="fixed inset-0 z-lightbox flex items-center justify-center p-6"
                 style="background: rgba(0,0,0,0.75)">
                <button @click="lightboxSrc = null"
                        class="absolute top-5 right-5 w-9 h-9 rounded-full flex items-center justify-center text-white cursor-pointer"
                        style="background: rgba(255,255,255,0.15)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <img :src="lightboxSrc" class="max-w-full max-h-full rounded-xl object-contain" style="box-shadow: 0 20px 60px rgba(0,0,0,0.5)">
            </div>
        </div>

        {{-- ── Comments ────────────────────────────────────────────────────────── --}}
        @php
            $comments         = $task->comments;
            $canDeleteComment = auth()->user()->hasPermission('tasks.comments.delete');
        @endphp
        <div class="mt-5 border-t border-hairline pt-4">
            <div id="agent-status-badge" class="mb-3"></div>
            <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-4">Comments</p>

            @if($comments->isNotEmpty())
            <div id="comments-list" class="space-y-4 mb-5">
                @foreach($comments as $comment)
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-0.5"
                         style="background: {{ $comment->author->color ?? '#D97757' }}">
                        {{ $comment->author->initials ?? strtoupper(substr($comment->author->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-[13px] font-semibold text-ink">{{ $comment->author->name }}</span>
                            <span class="text-[11px] text-muted">{{ $comment->created_at->diffForHumans() }}</span>
                            @if($canDeleteComment || $comment->user_id === auth()->id())
                            <form method="POST" action="{{ route('task-comments.destroy', ['task' => $task->id, 'comment' => $comment->id]) }}"
                                  class="ml-auto" onsubmit="return confirm('Delete this comment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-[11px] text-muted hover:text-[#b94040] transition-colors cursor-pointer">Delete</button>
                            </form>
                            @endif
                        </div>
                        <p class="text-[13px] text-dim leading-relaxed">{{ $comment->body }}</p>
                        {{-- Comment attachment --}}
                        @php $comAtt = $comment->getFirstMedia('attachment'); @endphp
                        @if($comAtt)
                        @php
                            $comIsImg = str_starts_with($comAtt->mime_type, 'image/');
                            $comDlUrl = route('comment-attachments.download', ['task' => $task->id, 'comment' => $comment->id]);
                            $comSize  = $comAtt->size >= 1048576 ? round($comAtt->size/1048576, 1).'MB' : round($comAtt->size/1024, 1).'KB';
                        @endphp
                        <div class="mt-1.5 flex items-center gap-2 py-1.5 px-2.5 rounded-lg bg-surface border border-hairline" style="max-width: fit-content">
                            @if($comIsImg)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                            </svg>
                            @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                            </svg>
                            @endif
                            <a href="{{ $comDlUrl }}" download="{{ $comAtt->file_name }}"
                               class="text-[11px] font-medium text-accent hover:underline truncate" style="max-width: 200px">{{ $comAtt->file_name }}</a>
                            <span class="text-[11px] text-muted shrink-0">{{ $comSize }}</span>
                            @if($comment->user_id === auth()->id() || $canDeleteComment)
                            <form method="POST" action="{{ route('comment-attachments.destroy', ['task' => $task->id, 'comment' => $comment->id]) }}"
                                  onsubmit="return confirm('Remove this attachment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-muted hover:text-[#b94040] cursor-pointer text-[11px] leading-none">×</button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-[13px] text-muted italic mb-5">No comments yet.</p>
            @endif

            {{-- Add comment --}}
            <form method="POST" action="{{ route('task-comments.store', $task) }}" enctype="multipart/form-data"
                  x-data="{ fileName: '' }">
                @csrf
                <div class="flex gap-3 items-start">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-0.5"
                         style="background: {{ auth()->user()->color ?? '#D97757' }}">
                        {{ auth()->user()->initials ?? strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex gap-2 items-start">
                            <textarea name="body"
                                      rows="2"
                                      placeholder="Write a comment…"
                                      class="flex-1 text-[13px] text-ink bg-surface border border-line rounded-lg px-3 py-2 outline-none focus:border-accent focus:bg-white transition-colors resize-none placeholder:text-muted placeholder:italic"></textarea>
                            <label class="w-8 h-8 flex items-center justify-center rounded-full text-muted hover:text-ink transition-colors cursor-pointer shrink-0 mt-0.5"
                                   style="background: rgba(0,0,0,0.07)" title="Attach file">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                                </svg>
                                <input type="file" name="attachment" class="sr-only"
                                       @change="fileName = $event.target.files[0]?.name ?? ''">
                            </label>
                            <button type="submit"
                                    class="px-3 py-2 bg-accent hover:bg-accent-hover text-white text-[12px] font-medium rounded-lg transition-colors shrink-0 self-start cursor-pointer">
                                Send
                            </button>
                        </div>
                        <div x-show="fileName" class="flex items-center gap-1.5 mt-1.5 text-[11px] text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32"/>
                            </svg>
                            <span x-text="fileName" class="truncate max-w-[200px]"></span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (!window.Echo) return;

    const taskId = {{ $task->id }};

    // Agent working indicator
    const agentBadge = document.getElementById('agent-status-badge');
    const commentsList = document.getElementById('comments-list');

    window.Echo.channel('tasks.' + taskId)
        .listen('.agent.started', (e) => {
            // Show animated loader on task
            if (agentBadge) {
                agentBadge.innerHTML = `
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 animate-pulse">
                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        ${e.agentName} working...
                    </span>`;
            }
        })
        .listen('.agent.completed', (e) => {
            // Update status badge and reload status section
            if (agentBadge) {
                agentBadge.innerHTML = `
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        ✅ Agent completed
                    </span>`;
            }
            // Auto-reload the page after 1 second to show new status
            setTimeout(() => window.location.reload(), 1000);
        })
        .listen('.agent.failed', (e) => {
            if (agentBadge) {
                agentBadge.innerHTML = `
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        ❌ Agent failed: ${e.error}
                    </span>`;
            }
        })
        .listen('.agent.comment', (e) => {
            // Append new comment without page reload
            if (commentsList) {
                const div = document.createElement('div');
                div.className = 'border rounded p-3 bg-gray-50 animate-pulse-once flex gap-3';
                div.innerHTML = `
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-0.5"
                         style="background: #D97757">
                        ${e.authorName.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium text-sm">${e.authorName}</span>
                            <span class="text-xs text-gray-400">just now</span>
                        </div>
                        <div class="text-sm text-gray-700 prose max-w-none">${e.body}</div>
                    </div>`;
                commentsList.prepend(div);
                // Remove pulse after animation
                setTimeout(() => div.classList.remove('animate-pulse-once'), 2000);
            }
        });
});
</script>

</x-layouts.app>
