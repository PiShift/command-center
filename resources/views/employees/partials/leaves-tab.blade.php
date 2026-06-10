<div x-show="tab === 'leaves'" x-cloak class="space-y-5">
    @php
        $currentYear = now()->year;
        $balancesByType = $employee->leaveBalances->keyBy('leave_type_id');
        $requests = $employee->leaveRequests;
        $canManageLeaves = auth()->user()->hasPermission('hr.manage') || (auth()->user()->employeeProfile?->id === $employee->id);
    @endphp

    @if(session('warning'))
    <div class="px-4 py-3 rounded-lg text-sm font-medium" style="background:#fef9ec;border:1px solid #f3e0a3;color:#9a7a1a">
        {{ session('warning') }}
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($leaveTypes as $leaveType)
            @php
                $balance = $balancesByType->get($leaveType->id) ?? $employee->getLeaveBalance($leaveType->id, $currentYear);
            @endphp
            <div class="rounded-xl p-4" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div>
                        <p class="font-semibold text-ink" style="font-size:14px">{{ $leaveType->name }}</p>
                        <p class="text-muted" style="font-size:12px">{{ $leaveType->code }}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:{{ $leaveType->color ?? '#f5f4ef' }}22;color:{{ $leaveType->color ?? '#8c8c8a' }}">{{ $leaveType->is_paid ? 'Paid' : 'Unpaid' }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg px-2 py-2" style="background:#faf9f5">
                        <p class="text-muted" style="font-size:11px">Allocated</p>
                        <p class="font-semibold text-ink" style="font-size:15px">{{ number_format((float) $balance->allocated_days, 1) }}</p>
                    </div>
                    <div class="rounded-lg px-2 py-2" style="background:#faf9f5">
                        <p class="text-muted" style="font-size:11px">Used</p>
                        <p class="font-semibold text-ink" style="font-size:15px">{{ number_format((float) $balance->used_days, 1) }}</p>
                    </div>
                    <div class="rounded-lg px-2 py-2" style="background:#faf9f5">
                        <p class="text-muted" style="font-size:11px">Remain</p>
                        <p class="font-semibold text-ink" style="font-size:15px">{{ number_format((float) $balance->remaining_days, 1) }}</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-muted">
                    <span>Carry over</span>
                    <span class="font-semibold text-ink">{{ number_format((float) $balance->carried_over_days, 1) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    @if($canManageLeaves)
    <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid #eeeee9">
            <div>
                <p class="font-semibold text-ink" style="font-size:14px">Request Leave</p>
                <p class="text-muted" style="font-size:12px">Submit a new leave request for this employee.</p>
            </div>
        </div>
        <div class="px-5 py-4" style="background:#faf9f5">
            <form method="POST" action="{{ route('employees.leaves.store', $employee) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Leave Type</label>
                        <select name="leave_type_id" required class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors" style="font-size:13px">
                            <option value="">Select leave type...</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Start Date</label>
                        <input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors" style="font-size:13px">
                    </div>
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">End Date</label>
                        <input type="date" name="end_date" value="{{ old('end_date', now()->toDateString()) }}" required class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors" style="font-size:13px">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Reason</label>
                        <textarea name="reason" rows="3" class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors" style="font-size:13px">{{ old('reason') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Notes</label>
                        <textarea name="notes" rows="3" class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors" style="font-size:13px">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-accent hover:bg-accent-hover text-white transition-colors cursor-pointer">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid #eeeee9">
            <p class="font-semibold text-ink" style="font-size:14px">Leave History</p>
            <span class="text-muted" style="font-size:12px">{{ $requests->count() }} requests</span>
        </div>
        @if($requests->isEmpty())
        <div class="px-5 py-10 text-center">
            <p class="text-muted" style="font-size:13px">No leave requests yet.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full" style="font-size:13px;min-width:860px">
                <thead>
                    <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                        <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Type</th>
                        <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Range</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Days</th>
                        <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Status</th>
                        <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Notes</th>
                        <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $request)
                    <tr style="border-bottom:1px solid #eeeee9">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $request->leaveType?->color ?? '#8c8c8a' }}"></span>
                                <div>
                                    <p class="text-ink font-medium" style="font-size:13px">{{ $request->leaveType?->name ?? 'Leave' }}</p>
                                    <p class="text-muted" style="font-size:12px">{{ $request->leaveType?->code }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3" style="color:#141413">{{ $request->start_date?->format('d M Y') }} - {{ $request->end_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right" style="font-weight:500;color:#141413">{{ number_format((float) ($request->days_actual ?? $request->days_requested), 1) }}</td>
                        <td class="px-4 py-3">
                            @if($request->status === 'approved')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#edf7f2;color:#2e7d55">Approved</span>
                            @elseif($request->status === 'rejected')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#fff0f0;color:#b94040">Rejected</span>
                            @elseif($request->status === 'cancelled')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#F5F4EF;color:#8c8c8a">Cancelled</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-[5px] text-[11px] font-semibold" style="background:#fef9ec;color:#9a7a1a">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3" style="color:#5c5c5a">
                            {{ $request->reason ?: '—' }}
                            @if($request->rejection_reason)
                            <div class="text-[12px] mt-1" style="color:#b94040">{{ $request->rejection_reason }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($request->status === 'pending' && $canManageLeaves)
                            <form method="POST" action="{{ route('employees.leaves.cancel', [$employee, $request]) }}" class="inline" onsubmit="return confirm('Cancel this leave request?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-danger-border bg-danger-light text-danger hover:bg-[#ffe0e0] transition-colors cursor-pointer">Cancel</button>
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
        @endif
    </div>
</div>
