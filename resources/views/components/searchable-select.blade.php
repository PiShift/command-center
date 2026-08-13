@props([
    'name' => null,
    'options' => [],
    'selected' => null,
    'multiple' => false,
    'placeholder' => 'Select...',
    'searchable' => null,
    'livewireModel' => null,
])

@php
    $normalizedOptions = collect($options)
        ->map(function ($option) {
            if (is_array($option)) {
                return [
                    'id' => (string) ($option['id'] ?? ''),
                    'label' => (string) ($option['label'] ?? ''),
                    'color' => $option['color'] ?? null,
                    'meta' => $option['meta'] ?? null,
                ];
            }

            if (is_object($option)) {
                return [
                    'id' => (string) ($option->id ?? ''),
                    'label' => (string) ($option->label ?? $option->name ?? ''),
                    'color' => $option->color ?? null,
                    'meta' => $option->meta ?? null,
                ];
            }

            return [
                'id' => (string) $option,
                'label' => (string) $option,
                'color' => null,
                'meta' => null,
            ];
        })
        ->filter(fn (array $option) => $option['id'] !== '' && $option['label'] !== '')
        ->values()
        ->all();

    $selectedValues = collect(is_array($selected) ? $selected : (filled($selected) ? [$selected] : []))
        ->map(fn ($value) => (string) $value)
        ->values()
        ->all();

    $isSearchable = $searchable ?? count($normalizedOptions) >= 8;
    $componentId = 'searchable-select-' . \Illuminate\Support\Str::uuid();
@endphp

<div
    x-data="searchableSelect({
        componentId: @js($componentId),
        options: @js($normalizedOptions),
        selected: @js($selectedValues),
        multiple: @js((bool) $multiple),
        placeholder: @js((string) $placeholder),
        searchable: @js((bool) $isSearchable),
        livewireModel: @js($livewireModel),
    })"
    x-on:keydown.escape.window="close()"
    class="relative"
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click.stop="toggle()"
        x-on:keydown.down.prevent="openAndHighlightFirst()"
        class="w-full min-w-44 rounded-lg border border-line bg-surface px-3 py-2 text-left text-[13px] text-ink transition-colors hover:border-muted focus:border-accent focus:bg-card focus:outline-none"
    >
        <span class="flex items-center gap-2">
            <template x-if="selectedBadgeColor()">
                <span class="h-2 w-2 rounded-full" :style="'background:' + selectedBadgeColor()"></span>
            </template>
            <span class="truncate" x-text="triggerLabel()"></span>
        </span>
        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-muted">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.150ms
        x-on:click.outside="close()"
        class="absolute z-40 mt-2 w-full rounded-xl border border-line bg-card p-2 shadow-[0_4px_20px_rgba(20,20,19,0.10)]"
    >
        <template x-if="searchable">
            <div class="mb-2">
                <input
                    type="text"
                    x-ref="search"
                    x-model="query"
                    x-on:keydown.down.prevent="move(1)"
                    x-on:keydown.up.prevent="move(-1)"
                    x-on:keydown.enter.prevent="selectHighlighted()"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[13px] text-ink placeholder:text-muted focus:border-accent focus:bg-card focus:outline-none"
                    placeholder="Search..."
                >
            </div>
        </template>

        <template x-if="multiple">
            <div class="mb-2 flex items-center justify-between border-b border-hairline pb-2">
                <button type="button" x-on:click="selectAllFiltered()" class="text-[11px] font-semibold uppercase tracking-wider text-dim transition-colors hover:text-ink cursor-pointer">Select All</button>
                <button type="button" x-on:click="clear()" class="text-[11px] font-semibold uppercase tracking-wider text-muted transition-colors hover:text-ink cursor-pointer">Clear</button>
            </div>
        </template>

        <template x-if="!multiple && selectedValues.length > 0">
            <div class="mb-2 flex items-center justify-end border-b border-hairline pb-2">
                <button type="button" x-on:click="clear()" class="text-[11px] font-semibold uppercase tracking-wider text-muted transition-colors hover:text-ink cursor-pointer">Clear</button>
            </div>
        </template>

        <div class="max-h-64 overflow-y-auto">
            <template x-if="filteredOptions().length === 0">
                <p class="px-2 py-3 text-[12px] text-muted">No results</p>
            </template>

            <template x-for="(option, index) in filteredOptions()" :key="option.id">
                <button
                    type="button"
                    x-on:click="select(option.id)"
                    x-on:mouseenter="highlightedIndex = index"
                    class="mb-1 flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-[13px] transition-colors cursor-pointer"
                    :class="highlightedIndex === index ? 'bg-canvas text-ink' : 'text-dim hover:bg-canvas hover:text-ink'"
                >
                    <template x-if="multiple">
                        <input type="checkbox" class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30" :checked="isSelected(option.id)">
                    </template>
                    <template x-if="!multiple">
                        <span class="h-2.5 w-2.5 rounded-full border border-line" :class="isSelected(option.id) ? 'bg-accent border-accent' : 'bg-card'"></span>
                    </template>

                    <template x-if="option.color">
                        <span class="h-2 w-2 rounded-full" :style="'background:' + option.color"></span>
                    </template>

                    <span class="flex-1 truncate" x-text="option.label"></span>

                    <template x-if="option.meta">
                        <span class="text-[11px] text-muted" x-text="option.meta"></span>
                    </template>
                </button>
            </template>
        </div>
    </div>

    @if($name)
        @if($multiple)
            <template x-for="value in selectedValues" :key="'input-' + value">
                <input type="hidden" name="{{ $name }}[]" :value="value">
            </template>
        @else
            <input type="hidden" name="{{ $name }}" :value="selectedValues[0] ?? ''">
        @endif
    @endif
</div>

