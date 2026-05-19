<x-layouts.app>
@section('title', 'Notifications')

<div class="max-w-3xl mx-auto py-8 px-4">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size:22px;font-weight:700;color:#141413">Notifications</h1>
        @if(auth()->user()->unreadNotifications()->count() > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit"
                    style="padding:7px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                    onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Mark all as read</button>
        </form>
        @endif
    </div>

    {{-- Tabs --}}
    @php $tabs = ['all' => 'All', 'unread' => 'Unread', 'tasks' => 'Tasks', 'sprints' => 'Sprints', 'projects' => 'Projects', 'teams' => 'Teams']; @endphp
    <div class="flex gap-1 mb-6" style="border-bottom:1px solid #e5e4df">
        @foreach($tabs as $key => $label)
        <a href="{{ route('notifications.index', ['tab' => $key]) }}"
           style="padding:8px 14px;font-size:13px;font-weight:{{ $tab === $key ? '600' : '400' }};color:{{ $tab === $key ? '#D97757' : '#5c5c5a' }};text-decoration:none;border-bottom:2px solid {{ $tab === $key ? '#D97757' : 'transparent' }};margin-bottom:-1px;transition:color 150ms ease">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Grouped by date --}}
    @php
        $grouped = $notifications->groupBy(fn($n) => $n->created_at->toDateString());
    @endphp

    @forelse($grouped as $date => $items)
    <div class="mb-6">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:8px">
            {{ \Carbon\Carbon::parse($date)->isToday() ? 'Today' : (\Carbon\Carbon::parse($date)->isYesterday() ? 'Yesterday' : \Carbon\Carbon::parse($date)->format('M d, Y')) }}
        </p>

        <div class="rounded-xl overflow-hidden" style="border:1px solid #e5e4df">
            @foreach($items as $i => $notification)
            @php $data = $notification->data; @endphp
            <div class="flex items-start gap-3 px-4 py-3 {{ $i < count($items) - 1 ? 'border-b' : '' }}"
                 style="{{ $i < count($items) - 1 ? 'border-bottom:1px solid #eeeee9;' : '' }}background:{{ $notification->read_at ? '#fff' : '#fdf3ee' }}">
                <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full mt-0.5" style="background:#F5F4EF">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#D97757" stroke-width="2">
                        @if(($data['icon'] ?? '') === 'check')
                            <polyline points="20 6 9 17 4 12"/>
                        @elseif(($data['icon'] ?? '') === 'users')
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        @elseif(($data['icon'] ?? '') === 'folder')
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        @else
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        @endif
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p style="font-size:13px;font-weight:{{ $notification->read_at ? '400' : '600' }};color:#141413;margin:0">
                        {{ $data['title'] ?? '' }}
                    </p>
                    @if(!empty($data['body']))
                    <p style="font-size:12px;color:#5c5c5a;margin:2px 0 0">{{ $data['body'] }}</p>
                    @endif
                    <p style="font-size:11px;color:#8c8c8a;margin:3px 0 0">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if(!empty($data['link']))
                    <a href="{{ $data['link'] }}"
                       style="font-size:12px;color:#D97757;text-decoration:none"
                       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View →</a>
                    @endif
                    @if(! $notification->read_at)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit" style="font-size:11px;color:#8c8c8a;background:none;border:none;cursor:pointer;padding:0"
                                onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">Mark read</button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="rounded-xl py-16 text-center" style="border:1px solid #e5e4df;background:#fff">
        <p style="font-size:14px;color:#8c8c8a">No notifications found.</p>
    </div>
    @endforelse

    {{-- Pagination --}}
    @if($notifications->hasPages())
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
</x-layouts.app>
