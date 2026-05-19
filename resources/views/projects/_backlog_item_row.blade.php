<li class="flex items-start gap-3 px-6 py-3.5 hover:bg-canvas transition-colors duration-150"
    :class="selectedItems.includes({{ $item->id }}) ? 'bg-accent-light' : ''"
    data-backlog-id="{{ $item->id }}"
    data-backlog-title="{{ e($item->title) }}"
    data-backlog-desc="{{ e(Str::limit($item->description ?? '', 200)) }}">
    @if($canManage)
    <div class="flex-shrink-0 mt-0.5">
        <input type="checkbox"
               @click="toggleItem({{ $item->id }})"
               :checked="selectedItems.includes({{ $item->id }})"
               class="w-4 h-4 rounded border-line text-accent cursor-pointer accent-[#D97757]">
    </div>
    @endif
    <div class="flex-1 min-w-0">
        {{-- Title row --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[13.5px] font-semibold text-ink">{{ $item->title }}</span>
            {{-- Status badge --}}
            @if($item->status === 'refined')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#eef3fb] text-[#3a6fba]">Refined</span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-surface text-muted">Raw</span>
            @endif
            {{-- Guide indicator --}}
            @if($item->guide)
                <span title="Has guide" class="text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </span>
            @endif
        </div>
        {{-- Description preview --}}
        @if($item->description)
            <p class="text-[12px] text-muted mt-0.5 truncate">{{ Str::limit($item->description, 100) }}</p>
        @endif
    </div>

    {{-- Actions --}}
    @if($canManage)
    <div class="flex items-center gap-1 flex-shrink-0 mt-0.5">
        {{-- Edit --}}
        <button
            @click="open({
                id: {{ $item->id }},
                title: {{ Js::from($item->title) }},
                description: {{ Js::from($item->description) }},
                guide: {{ Js::from($item->guide) }},
                sprint_id: '{{ $item->sprint_id ?? '' }}'
            })"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-hairline transition-colors duration-150 cursor-pointer"
            title="Edit">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
            </svg>
        </button>
        {{-- Delete --}}
        <button
            x-data
            @click="if(confirm('Delete this backlog item? This cannot be undone.')) $refs.deleteBacklog{{ $item->id }}.submit()"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-muted hover:text-[#b94040] hover:bg-[#fff0f0] transition-colors duration-150 cursor-pointer"
            title="Delete">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
            </svg>
        </button>
        <form x-ref="deleteBacklog{{ $item->id }}"
              action="{{ route('backlog.destroy', [$project, $item]) }}"
              method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
        {{-- Promote (only for non-promoted items) --}}
        @if(!$item->promoted)
        <button
            @click="openPromote({
                id: {{ $item->id }},
                title: {{ Js::from($item->title) }},
                description: {{ Js::from($item->description) }},
                sprint_id: '{{ $item->sprint_id ?? '' }}'
            })"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-[#2e7d55] hover:bg-[#edf7f2] transition-colors duration-150 cursor-pointer"
            title="Promote to task">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
            </svg>
        </button>
        @else
        @if($item->promotedTask)
        <a href="{{ route('tasks.show', $item->promotedTask) }}"
           class="inline-flex items-center h-7 px-2 text-[11px] font-medium text-accent hover:underline flex-shrink-0"
           title="View promoted task">
            → View Task
        </a>
        @endif
        @endif
    </div>
    @endif
</li>
