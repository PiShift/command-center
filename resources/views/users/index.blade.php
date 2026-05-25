<x-layouts.app title="Team">

@php
    $sortLink = fn(string $col) => request()->fullUrlWithQuery([
        'sort'      => $col,
        'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
        'page'      => 1,
    ]);
@endphp

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px; font-weight:600; color:#141413">Team</h1>
    @if(auth()->user()->hasPermission('users.create'))
    <a href="{{ route('users.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + Add member
    </a>
    @endif
</div>

@include('components.flash')

{{-- Table --}}
<div class="rounded-xl" style="background:#fff; border:1px solid #e5e4df; box-shadow:0 1px 3px rgba(20,20,19,0.04)">
    @if($users->isEmpty())
        <div class="py-16 text-center" style="color:#8c8c8a; font-size:13px">No team members.</div>
    @else
    <div class="overflow-x-auto">
    <table class="w-full" style="font-size:13.5px;min-width:560px">
        <thead>
            <tr style="background:#faf9f5; border-bottom:1px solid #e5e4df">
                @php
                    $headers = [
                        ['col' => 'name',  'label' => 'Name',  'cls' => 'px-6 py-3 text-left'],
                        ['col' => 'email', 'label' => 'Email', 'cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                        ['col' => null,    'label' => 'Role',  'cls' => 'px-4 py-3 text-left'],
                        ['col' => null,    'label' => '',      'cls' => 'px-4 py-3'],
                    ];
                @endphp
                @foreach($headers as $th)
                <th class="{{ $th['cls'] }}" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;white-space:nowrap">
                    @if($th['col'])
                        <a href="{{ $sortLink($th['col']) }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            {{ $th['label'] }}
                            <span style="color:{{ $sort === $th['col'] ? '#D97757' : '#d8d7d2' }}">{!! $sort === $th['col'] ? ($direction === 'asc' ? '↑' : '↓') : '↕' !!}</span>
                        </a>
                    @else
                        {{ $th['label'] }}
                    @endif
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($users as $member)
            <tr style="background:#fff;border-bottom:1px solid #eeeee9;transition:background 120ms ease"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='#fff'">
                <td class="px-6 py-3">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold text-white flex-shrink-0"
                              style="background:{{ $member->color ?? '#D97757' }}">
                            {{ $member->initials ?? strtoupper(substr($member->name, 0, 2)) }}
                        </span>
                        <span style="font-weight:500;color:#141413">{{ $member->name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 hidden sm:table-cell" style="color:#5c5c5a">{{ $member->email }}</td>
                <td class="px-4 py-3">
                    @if($member->roleModel)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold text-white"
                              style="background:{{ $member->roleModel->color ?? '#5c5c5a' }}">
                            {{ $member->roleModel->name }}
                        </span>
                    @else
                        <span style="color:#8c8c8a">-</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if(auth()->user()->hasPermission('users.edit'))
                        <a href="{{ route('users.edit', $member) }}" title="Edit" style="color:#8c8c8a;transition:color 120ms ease" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'pencil'])</a>
                        @endif
                        @if(auth()->user()->hasPermission('users.delete') && $member->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $member) }}" onsubmit="return confirm('Remove this team member?')" class="inline">@csrf @method('DELETE')
                            <button type="submit" style="color:#8c8c8a;background:none;border:none;cursor:pointer;padding:0;transition:color 120ms ease" onmouseover="this.style.color='#b94040'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'trash'])</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="mt-4 px-4 pb-4" style="border-top:1px solid #eeeee9">{{ $users->links() }}</div>
    @endif
</div>

</x-layouts.app>
