<div class="relative" x-data="{ open: false }" wire:poll.30s="loadCount">
    {{-- Bell button --}}
    <button @click="open = !open"
            class="relative flex items-center justify-center w-8 h-8 rounded-lg transition-colors hover:bg-hairline"
            style="color:#5c5c5a">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        @if($unreadCount > 0)
        <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center"
              style="min-width:16px;height:16px;background:#D97757;color:#fff;border-radius:20px;font-size:10px;font-weight:700;padding:0 4px;line-height:1">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" @click.outside="open = false" x-cloak
         style="position:absolute;right:0;top:calc(100% + 8px);width:340px;background:#fff;border:1px solid #e5e4df;border-radius:12px;box-shadow:0 8px 32px rgba(20,20,19,0.12);z-index:50;overflow:hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid #e5e4df">
            <span style="font-size:13px;font-weight:600;color:#141413">Notifications</span>
            @if($unreadCount > 0)
            <button wire:click="markAllRead" style="font-size:12px;color:#D97757;background:none;border:none;cursor:pointer;padding:0"
                    onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Mark all as read</button>
            @endif
        </div>

        {{-- Items --}}
        <div style="max-height:360px;overflow-y:auto">
            @forelse($notifications as $notification)
            @php $data = $notification->data; @endphp
            <a href="{{ isset($data['link']) ? $data['link'] : route('notifications.index') }}"
               wire:click="markRead('{{ $notification->id }}')"
               class="flex items-start gap-3 px-4 py-3 transition-colors"
               style="display:flex;text-decoration:none;border-bottom:1px solid #eeeee9;background:{{ $notification->read_at ? '#fff' : '#fdf3ee' }}"
               onmouseover="this.style.background='#F5F4EF'" onmouseout="this.style.background='{{ $notification->read_at ? '#fff' : '#fdf3ee' }}'">
                <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full mt-0.5" style="background:#F5F4EF">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#D97757" stroke-width="2">
                        @if(($data['icon'] ?? '') === 'check')
                            <polyline points="20 6 9 17 4 12"/>
                        @elseif(($data['icon'] ?? '') === 'users')
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        @elseif(($data['icon'] ?? '') === 'folder')
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        @else
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        @endif
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p style="font-size:13px;font-weight:{{ $notification->read_at ? '400' : '600' }};color:#141413;margin:0;line-height:1.4">
                        {{ $data['title'] ?? '' }}
                    </p>
                    @if(!empty($data['body']))
                    <p style="font-size:12px;color:#5c5c5a;margin:2px 0 0;line-height:1.3">{{ $data['body'] }}</p>
                    @endif
                    <p style="font-size:11px;color:#8c8c8a;margin:3px 0 0">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @if(! $notification->read_at)
                <div class="shrink-0 w-2 h-2 rounded-full mt-2" style="background:#D97757"></div>
                @endif
            </a>
            @empty
            <div class="px-4 py-8 text-center" style="color:#8c8c8a;font-size:13px">No notifications yet</div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="px-4 py-2" style="border-top:1px solid #e5e4df;text-align:center">
            <a href="{{ route('notifications.index') }}" @click="open = false"
               style="font-size:12px;color:#D97757;text-decoration:none"
               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View all notifications</a>
        </div>
    </div>
</div>
