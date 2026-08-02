<x-layouts.app title="Customers">

@php
    $sortLink = fn(string $col) => request()->fullUrlWithQuery([
        'sort'      => $col,
        'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
        'page'      => 1,
    ]);
@endphp

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px; font-weight:600; color:#141413">Customers</h1>
    @if(auth()->user()->hasPermission('customers.create'))
    <a href="{{ route('customers.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New customer
    </a>
    @endif
</div>

@include('components.flash')

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="direction" value="{{ $direction }}">

    <div class="relative">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customers..."
               class="text-[13px] pl-3 pr-3 py-2 rounded-lg"
               style="background:#F5F4EF; border:1px solid #e5e4df; color:#141413; outline:none; width:190px">
    </div>

    @foreach([
        ['name' => 'status',   'label' => 'All Statuses',  'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'prospect' => 'Prospect']],
    ] as $f)
    <div class="relative">
        <select name="{{ $f['name'] }}" onchange="this.form.submit()"
                class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer"
                style="background:#F5F4EF; border:1px solid #e5e4df; color:#141413; outline:none">
            <option value="">{{ $f['label'] }}</option>
            @foreach($f['options'] as $val => $lab)
                <option value="{{ $val }}" {{ request($f['name']) == $val ? 'selected' : '' }}>{{ $lab }}</option>
            @endforeach
        </select>
        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </div>
    @endforeach

    @if(request()->hasAny(['search','status','industry']))
        <a href="{{ route('customers.index') }}" style="display:flex;align-items:center;padding:8px 12px;font-size:13px;color:#8c8c8a;text-decoration:none;border-radius:8px"
           onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">x Clear</a>
    @endif
</form>

{{-- Table --}}
<div class="rounded-xl" style="background:#fff; border:1px solid #e5e4df; box-shadow:0 1px 3px rgba(20,20,19,0.04)">
    @if($customers->isEmpty())
        <div class="py-16 text-center" style="color:#8c8c8a; font-size:13px">No customers yet.</div>
    @else
    <div class="overflow-x-auto">
    <table class="w-full" style="font-size:13.5px;min-width:600px">
        <thead>
            <tr style="background:#faf9f5; border-bottom:1px solid #e5e4df">
                @php
                    $headers = [
                        ['col' => 'name',    'label' => 'Name',     'cls' => 'px-6 py-3 text-left'],
                        ['col' => 'company', 'label' => 'Company',  'cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                        ['col' => 'status',  'label' => 'Status',   'cls' => 'px-4 py-3 text-left'],
                        ['col' => 'industry','label' => 'Industry', 'cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                        ['col' => null,      'label' => 'Projects', 'cls' => 'px-4 py-3 text-left hidden sm:table-cell'],
                        ['col' => null,      'label' => '',         'cls' => 'px-4 py-3'],
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
            @foreach($customers as $customer)
            <tr style="background:#fff;border-bottom:1px solid #eeeee9;transition:background 120ms ease"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='#fff'">
                <td class="px-6 py-3">
                    <a href="{{ route('customers.show', $customer) }}" style="font-weight:500;color:#141413;text-decoration:none" onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">{{ $customer->name }}</a>
                    @if($customer->email)<p style="font-size:11px;color:#8c8c8a;margin-top:2px">{{ $customer->email }}</p>@endif
                </td>
                <td class="px-4 py-3 hidden sm:table-cell" style="color:#5c5c5a">{{ $customer->company ?? '-' }}</td>
                <td class="px-4 py-3">@include('components.badge', ['type' => 'customer_status', 'value' => $customer->status])</td>
                <td class="px-4 py-3 hidden sm:table-cell" style="color:#5c5c5a">{{ $customer->industry ?? '-' }}</td>
                <td class="px-4 py-3 hidden sm:table-cell" style="color:#5c5c5a">{{ $customer->projects_count }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if(auth()->user()->hasPermission('customers.edit'))
                        <a href="{{ route('customers.edit', $customer) }}" title="Edit" style="color:#8c8c8a;transition:color 120ms ease" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">@include('components.icon', ['name' => 'pencil'])</a>
                        @endif
                        @if(auth()->user()->hasPermission('customers.delete'))
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')" class="inline">@csrf @method('DELETE')
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
    <div class="mt-4 px-4 pb-4" style="border-top:1px solid #eeeee9">{{ $customers->links() }}</div>
    @endif
</div>

</x-layouts.app>
