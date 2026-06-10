<x-layouts.app :title="'HR Leaves'">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700">Human Resources</p>
                <h1 style="font-size:24px;font-weight:600;color:#141413">Leave Management</h1>
            </div>
            <a href="{{ route('leave-types.index') }}" class="font-medium rounded-lg px-3 py-2 transition-colors" style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">Manage Leave Types</a>
        </div>

        <div class="flex items-center gap-1" style="border-bottom:1px solid #e5e4df;padding-bottom:0">
            <a href="{{ route('leaves.index') }}" class="px-4 py-2 font-medium transition-all" style="font-size:13px;border-bottom:2px solid {{ $activeTab === 'all' ? '#D97757' : 'transparent' }};color:{{ $activeTab === 'all' ? '#141413' : '#8c8c8a' }};margin-bottom:-1px">All Requests</a>
            <a href="{{ route('leaves.calendar') }}" class="px-4 py-2 font-medium transition-all" style="font-size:13px;border-bottom:2px solid {{ $activeTab === 'calendar' ? '#D97757' : 'transparent' }};color:{{ $activeTab === 'calendar' ? '#141413' : '#8c8c8a' }};margin-bottom:-1px">Calendar</a>
        </div>

        @if(session('success'))
        <div class="px-4 py-3 rounded-lg text-sm font-medium" style="background:#edf7f2;border:1px solid #c6e8d5;color:#2e7d55">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="px-4 py-3 rounded-lg text-sm font-medium" style="background:#fff0f0;border:1px solid #ffd0d0;color:#b94040">{{ session('error') }}</div>
        @endif

        @if($activeTab === 'all')
        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <div class="px-5 py-4 flex items-center justify-between gap-3" style="border-bottom:1px solid #eeeee9">
                <div>
                    <p class="font-semibold text-ink" style="font-size:14px">All Requests</p>
                    <p class="text-muted" style="font-size:12px">Newest requests first.</p>
                </div>
                <form method="GET" action="{{ route('leaves.index') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Status</label>
                        <select name="status" class="text-ink bg-surface border border-line rounded-lg px-3 py-2" style="font-size:13px">
                            <option value="">All</option>
                            <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Pending</option>
                            <option value="approved" @selected(($filters['status'] ?? null) === 'approved')>Approved</option>
                            <option value="rejected" @selected(($filters['status'] ?? null) === 'rejected')>Rejected</option>
                            <option value="cancelled" @selected(($filters['status'] ?? null) === 'cancelled')>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Leave Type</label>
                        <select name="leave_type_id" class="text-ink bg-surface border border-line rounded-lg px-3 py-2" style="font-size:13px">
                            <option value="">All</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" @selected((string) ($filters['leave_type_id'] ?? '') === (string) $leaveType->id)>{{ $leaveType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Month</label>
                        <input type="month" name="month" value="{{ $filters['month'] ?? '' }}" class="text-ink bg-surface border border-line rounded-lg px-3 py-2" style="font-size:13px">
                    </div>
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Year</label>
                        <input type="number" name="year" min="2020" max="{{ now()->year + 1 }}" value="{{ $filters['year'] ?? '' }}" class="text-ink bg-surface border border-line rounded-lg px-3 py-2" style="font-size:13px;width:100px">
                    </div>
                    <button type="submit" class="font-medium rounded-lg px-3 py-2 text-white transition-colors bg-accent hover:bg-accent-hover" style="font-size:13px">Filter</button>
                </form>
            </div>

            @if($leaveRequests->isEmpty())
            <div class="px-5 py-10 text-center">
                <p class="text-muted" style="font-size:13px">No leave requests found.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full" style="font-size:13px;min-width:1050px">
                    <thead>
                        <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                            <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Employee</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Type</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Range</th>
                            <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Days</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Status</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Approver</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Notes</th>
                            <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaveRequests as $leaveRequest)
                        <tr style="border-bottom:1px solid #eeeee9">
                            <td class="px-5 py-3">
                                <a href="{{ route('employees.show', $leaveRequest->employee) }}" class="font-medium" style="color:#141413;font-size:13px">{{ $leaveRequest->employee?->display_name }}</a>
                                <div class="text-muted" style="font-size:12px">{{ $leaveRequest->employee?->employee_number }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $leaveRequest->leaveType?->color ?? '#8c8c8a' }}"></span>
                                    <span style="color:#141413">{{ $leaveRequest->leaveType?->name ?? 'Leave' }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3" style="color:#141413">{{ $leaveRequest->start_date?->format('d M Y') }} - {{ $leaveRequest->end_date?->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right" style="font-weight:500;color:#141413">{{ number_format((float) ($leaveRequest->days_actual ?? $leaveRequest->days_requested), 1) }}</td>
                            <td class="px-4 py-3">
                                @if($leaveRequest->status === 'approved')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#edf7f2;color:#2e7d55">Approved</span>
                                @elseif($leaveRequest->status === 'rejected')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#fff0f0;color:#b94040">Rejected</span>
                                @elseif($leaveRequest->status === 'cancelled')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#F5F4EF;color:#8c8c8a">Cancelled</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#fef9ec;color:#9a7a1a">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3" style="color:#5c5c5a">{{ $leaveRequest->approver?->name ?? '—' }}</td>
                            <td class="px-4 py-3" style="color:#5c5c5a">{{ $leaveRequest->reason ?: '—' }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if(auth()->user()->hasPermission('hr.manage') && $leaveRequest->status === 'pending')
                                <form method="POST" action="{{ route('leaves.approve', $leaveRequest) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-line bg-surface text-ink hover:bg-[#f7f6f2] transition-colors cursor-pointer">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('leaves.reject', $leaveRequest) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="rejection_reason" value="Rejected from list">
                                    <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-danger-border bg-danger-light text-danger hover:bg-[#ffe0e0] transition-colors cursor-pointer">Reject</button>
                                </form>
                                @else
                                <span class="text-muted" style="font-size:12px">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-hairline">
                {{ $leaveRequests->links() }}
            </div>
            @endif
        </div>
        @else
        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3" style="border-bottom:1px solid #eeeee9">
                <div>
                    <p class="font-semibold text-ink" style="font-size:14px">Calendar</p>
                    <p class="text-muted" style="font-size:12px">{{ $calendar['selectedMonth']->format('F Y') }}</p>
                </div>
                <form method="GET" action="{{ route('leaves.calendar') }}" class="flex items-end gap-2">
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Month</label>
                        <input type="month" name="month" value="{{ $calendar['selectedMonth']->format('Y-m') }}" class="text-ink bg-surface border border-line rounded-lg px-3 py-2" style="font-size:13px">
                    </div>
                    <button type="submit" class="font-medium rounded-lg px-3 py-2 text-white transition-colors bg-accent hover:bg-accent-hover" style="font-size:13px">Go</button>
                </form>
            </div>
            <div class="px-5 py-4 border-b border-hairline flex flex-wrap gap-3">
                @foreach($leaveTypes as $leaveType)
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium" style="background:#faf9f5;color:#141413">
                    <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $leaveType->color ?? '#8c8c8a' }}"></span>
                    {{ $leaveType->name }}
                </span>
                @endforeach
            </div>
            <div class="grid grid-cols-7 text-center text-xs font-semibold uppercase tracking-wider" style="background:#faf9f5;color:#8c8c8a">
                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dayLabel)
                <div class="px-2 py-3">{{ $dayLabel }}</div>
                @endforeach
            </div>
            <div class="divide-y divide-hairline">
                @foreach($calendar['weeks'] as $week)
                <div class="grid grid-cols-7 divide-x divide-hairline">
                    @foreach($week as $cell)
                    <div class="min-h-[150px] p-3" style="background:{{ $cell['in_month'] ? '#fff' : '#fcfbf8' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold {{ $cell['in_month'] ? 'text-ink' : 'text-muted' }}">{{ $cell['date']->day }}</span>
                            <span class="text-[11px] text-muted">{{ $cell['requests'] ? count($cell['requests']) . ' leave(s)' : '' }}</span>
                        </div>
                        <div class="space-y-2">
                            @foreach($cell['requests'] as $leaveRequest)
                            <a href="{{ route('employees.show', $leaveRequest->employee) }}#leaves" class="block rounded-lg px-2 py-2 transition-colors hover:opacity-90" style="background:{{ ($leaveRequest->leaveType?->color ?? '#D97757') . '22' }};border:1px solid {{ $leaveRequest->leaveType?->color ?? '#D97757' }}33">
                                <p class="text-xs font-semibold" style="color:#141413">{{ $leaveRequest->employee?->display_name }}</p>
                                <p class="text-[11px]" style="color:#5c5c5a">{{ $leaveRequest->leaveType?->name }}</p>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-layouts.app>
