@props([
    'text',
    'position' => 'top', {{-- top | bottom | start | end --}}
])

@php
$posClasses = match($position) {
    'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
    'start'  => 'right-full top-1/2 -translate-y-1/2 mr-2',
    'end'    => 'left-full top-1/2 -translate-y-1/2 ml-2',
    default  => 'bottom-full left-1/2 -translate-x-1/2 mb-2', // top
};
@endphp

<span class="relative inline-flex items-center cursor-default group">
    @if($slot->isEmpty())
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="8"/>
        <path d="M12 12v4"/>
    </svg>
    @else
    {{ $slot }}
    @endif
    <span class="absolute {{ $posClasses }} px-2 py-1 text-[11px] text-white bg-gray-900 rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap w-max max-w-[220px]">
        {{ $text }}
    </span>
</span>
