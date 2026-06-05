<x-layouts.app title="Employees">

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px;font-weight:600;color:#141413">
        Employees
        <span class="ml-2 text-muted font-normal" style="font-size:14px">{{ $employees->total() }}</span>
    </h1>
    @if(auth()->user()->hasPermission('hr.manage'))
    <a href="{{ route('employees.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New Employee
    </a>
    @endif
</div>

@include('components.flash')

{{-- Filters --}}
<form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap gap-2 mb-4">
    <select name="status" onchange="this.form.submit()"
            class="text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer appearance-none"
            style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
        <option value="">All Statuses</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="on_leave" @selected(request('status') === 'on_leave')>On Leave</option>
        <option value="terminated" @selected(request('status') === 'terminated')>Terminated</option>
    </select>
    <select name="employment_type" onchange="this.form.submit()"
            class="text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer appearance-none"
            style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
        <option value="">All Types</option>
        <option value="CDI" @selected(request('employment_type') === 'CDI')>CDI</option>
        <option value="CDD" @selected(request('employment_type') === 'CDD')>CDD</option>
        <option value="freelance" @selected(request('employment_type') === 'freelance')>Freelance</option>
        <option value="internship" @selected(request('employment_type') === 'internship')>Internship</option>
        <option value="part_time" @selected(request('employment_type') === 'part_time')>Part-time</option>
    </select>
</form>

<div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
    <table class="w-full" style="border-collapse:collapse">
                    <thead>
                        <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                            <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Employee</th>
                            <th class="px-4 py-3 text-left hidden md:table-cell" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Job Title</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Type</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Status</th>
                            <th class="px-4 py-3 text-right hidden lg:table-cell" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Salary</th>
                            <th class="px-4 py-3 text-left hidden md:table-cell" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Since</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                        @php
                            $contract = $employee->current_contract;
                            $initials = collect(explode(' ', $employee->display_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
                        @endphp
                        <tr class="border-b border-hairline hover:bg-canvas transition-colors" style="cursor:pointer" onclick="window.location='{{ route('employees.show', $employee) }}'">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if($employee->getFirstMediaUrl('avatar'))
                                    <img src="{{ $employee->getFirstMediaUrl('avatar') }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0" alt="{{ $employee->display_name }}">
                                    @else
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-white font-semibold" style="font-size:13px;background:#D97757">
                                        {{ $initials }}
                                    </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-ink" style="font-size:13.5px">{{ $employee->display_name }}</div>
                                        <div class="text-muted" style="font-size:11px">{{ $employee->employee_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-dim" style="font-size:13px">{{ $employee->job_title ?: '—' }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                @php
                                    $typeColors = ['CDI'=>['#edf7f2','#2e7d55'],'CDD'=>['#eef3fb','#3a6fba'],'freelance'=>['#fdf3ee','#b55a2f'],'internship'=>['#fef9ec','#9a7a1a'],'part_time'=>['#F5F4EF','#5c5c5a']];
                                    [$typeBg,$typeText] = $typeColors[$employee->employment_type] ?? ['#F5F4EF','#5c5c5a'];
                                @endphp
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:{{ $typeBg }};color:{{ $typeText }}">{{ $employee->employment_type }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = ['active'=>['#edf7f2','#2e7d55'],'on_leave'=>['#fef9ec','#9a7a1a'],'terminated'=>['#F5F4EF','#8c8c8a']];
                                    [$sBg,$sText] = $statusColors[$employee->status] ?? ['#F5F4EF','#8c8c8a'];
                                @endphp
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:{{ $sBg }};color:{{ $sText }}">{{ str_replace('_', ' ', ucfirst($employee->status)) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right hidden lg:table-cell text-dim" style="font-size:13px">
                                @if($contract)
                                    {{ $contract->currency }} {{ number_format($contract->base_salary, 0) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-muted" style="font-size:12px">
                                {{ $employee->start_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('employees.show', $employee) }}"
                                   class="text-muted hover:text-ink transition-colors"
                                   style="font-size:12px"
                                   onclick="event.stopPropagation()">
                                    View →
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="text-muted" style="font-size:14px;font-weight:500;margin-bottom:4px">No employees found</div>
                                <div class="text-muted" style="font-size:13px">Add your first employee to get started.</div>
                                @if(auth()->user()->hasPermission('hr.manage'))
                                <div class="mt-4">
                                    <a href="{{ route('employees.create') }}"
                                       class="inline-flex items-center gap-1.5 text-white font-medium rounded-lg px-4 py-2 bg-accent hover:bg-accent-hover transition-colors"
                                       style="font-size:13px">
                                        + New Employee
                                    </a>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

@if($employees->hasPages())
<div class="mt-4 flex justify-between items-center text-muted" style="font-size:13px">
    <span>Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }}</span>
    <div class="flex gap-2">
        @if($employees->onFirstPage())
        <span class="px-3 py-1 rounded border border-line text-muted" style="cursor:not-allowed">← Prev</span>
        @else
        <a href="{{ $employees->previousPageUrl() }}" class="px-3 py-1 rounded border border-line text-dim hover:text-ink transition-colors">← Prev</a>
        @endif
        @if($employees->hasMorePages())
        <a href="{{ $employees->nextPageUrl() }}" class="px-3 py-1 rounded border border-line text-dim hover:text-ink transition-colors">Next →</a>
        @else
        <span class="px-3 py-1 rounded border border-line text-muted" style="cursor:not-allowed">Next →</span>
        @endif
    </div>
</div>
@endif

</x-layouts.app>
