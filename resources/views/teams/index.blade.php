<x-layouts.app title="Teams">

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px;font-weight:600;color:#141413">Teams</h1>
    @if(auth()->user()->hasPermission('teams.manage'))
    <a href="{{ route('teams.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New Team
    </a>
    @endif
</div>

@include('components.flash')

@if($teams->isEmpty())
    <div class="flex flex-col items-center justify-center py-20" style="color:#8c8c8a">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <p style="font-size:14px;font-weight:500;color:#5c5c5a">No teams yet</p>
        <p style="font-size:13px;margin-top:4px">Create your first team to organize people across projects.</p>
        @if(auth()->user()->hasPermission('teams.manage'))
        <a href="{{ route('teams.create') }}"
           style="margin-top:16px;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
           onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
            + New Team
        </a>
        @endif
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
        @foreach($teams as $team)
        <div x-data="{ confirmDelete: false }"
             style="background:#fff;border:1px solid #eeeee9;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(20,20,19,0.04);transition:box-shadow 150ms ease,border-color 150ms ease;display:flex;flex-direction:column;gap:12px"
             onmouseover="this.style.boxShadow='0 4px 14px rgba(20,20,19,0.08)';this.style.borderColor='#e5e4df'"
             onmouseout="this.style.boxShadow='0 1px 3px rgba(20,20,19,0.04)';this.style.borderColor='#eeeee9'">

            {{-- Header --}}
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
                <div>
                    <a href="{{ route('teams.show', $team) }}"
                       style="font-size:15px;font-weight:600;color:#141413;text-decoration:none;transition:color 150ms ease"
                       onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">
                        {{ $team->name }}
                    </a>
                    @if($team->description)
                    <p style="font-size:13px;color:#5c5c5a;margin-top:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                        {{ $team->description }}
                    </p>
                    @endif
                </div>
                @if(auth()->user()->hasPermission('teams.manage'))
                <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
                    <a href="{{ route('teams.edit', $team) }}"
                       style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(0,0,0,0.07);transition:background 120ms ease;color:#5c5c5a"
                       onmouseover="this.style.background='rgba(0,0,0,0.13)';this.style.color='#141413'" onmouseout="this.style.background='rgba(0,0,0,0.07)';this.style.color='#5c5c5a'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </a>
                    <button type="button"
                            @click="confirmDelete = true"
                            style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(0,0,0,0.07);transition:background 120ms ease;border:none;cursor:pointer;color:#5c5c5a"
                            onmouseover="this.style.background='#fff0f0';this.style.color='#b94040'" onmouseout="this.style.background='rgba(0,0,0,0.07)';this.style.color='#5c5c5a'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </div>
                @endif
            </div>

            {{-- Lead --}}
            @if($team->lead)
            <div style="display:flex;align-items:center;gap:6px">
                <span style="width:22px;height:22px;border-radius:50%;background:{{ $team->lead->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0">
                    {{ $team->lead->initials ?? strtoupper(substr($team->lead->name, 0, 2)) }}
                </span>
                <span style="font-size:12px;color:#5c5c5a">{{ $team->lead->name }}</span>
                <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;background:#F5F4EF;padding:1px 6px;border-radius:4px">Lead</span>
            </div>
            @endif

            {{-- Member count + avatar stack --}}
            <div style="display:flex;align-items:center;gap:8px">
                {{-- Avatar stack (first 4) --}}
                <div style="display:flex;align-items:center">
                    @foreach($team->members->take(4) as $i => $member)
                    <span style="width:28px;height:28px;border-radius:50%;background:{{ $member->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;border:2px solid #fff;{{ $i > 0 ? 'margin-left:-8px' : '' }};flex-shrink:0;z-index:{{ 4 - $i }}">
                        {{ $member->initials ?? strtoupper(substr($member->name, 0, 2)) }}
                    </span>
                    @endforeach
                    @if($team->members_count > 4)
                    <span style="width:28px;height:28px;border-radius:50%;background:#F5F4EF;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;color:#8c8c8a;border:2px solid #fff;margin-left:-8px">
                        +{{ $team->members_count - 4 }}
                    </span>
                    @endif
                </div>
                <span style="font-size:12px;color:#8c8c8a;font-weight:500">
                    {{ $team->members_count }} {{ $team->members_count === 1 ? 'member' : 'members' }}
                </span>
            </div>

            {{-- Delete confirmation --}}
            @if(auth()->user()->hasPermission('teams.manage'))
            <div x-show="confirmDelete" x-cloak
                 style="border-top:1px solid #ffd0d0;padding-top:12px;margin-top:4px">
                <p style="font-size:12px;color:#b94040;margin-bottom:8px">Delete <strong>{{ $team->name }}</strong>? This cannot be undone.</p>
                <div style="display:flex;gap:8px">
                    <button type="button" @click="confirmDelete = false"
                            style="padding:5px 12px;font-size:12px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:6px;cursor:pointer;color:#141413;transition:background 150ms ease"
                            onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">
                        Cancel
                    </button>
                    <form method="POST" action="{{ route('teams.destroy', $team) }}" style="margin:0">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="padding:5px 12px;font-size:12px;font-weight:500;background:#fff0f0;border:1px solid #ffd0d0;border-radius:6px;cursor:pointer;color:#b94040;transition:background 150ms ease"
                                onmouseover="this.style.background='#ffe0e0'" onmouseout="this.style.background='#fff0f0'">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
@endif

</x-layouts.app>
