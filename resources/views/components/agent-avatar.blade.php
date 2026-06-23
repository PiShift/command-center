@props([
    'agent',
    'size' => 8,
])

@php
    $px = max(4, (int) $size) * 4;
    $avatarUrl = $agent?->getFirstMediaUrl('avatar', 'thumb') ?: $agent?->getFirstMediaUrl('avatar');
@endphp

@if($avatarUrl)
    <img
        src="{{ $avatarUrl }}"
        alt="{{ $agent?->name ?? 'Agent avatar' }}"
        class="rounded-lg object-cover shrink-0"
        style="width: {{ $px }}px; height: {{ $px }}px;"
    >
@else
    <div class="rounded-lg shrink-0 flex items-center justify-center bg-gradient-to-br from-violet-700 to-purple-500" style="width: {{ $px }}px; height: {{ $px }}px;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-[62%] h-[62%] text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2zM9 14a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm6 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
        </svg>
    </div>
@endif