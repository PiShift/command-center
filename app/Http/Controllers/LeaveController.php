<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasPermission('hr.view'), 403);

        $employeeId = $request->integer('employee_id') ?: null;
        $leaveTypeId = $request->integer('leave_type_id') ?: null;
        $status = $request->string('status')->toString() ?: null;
        $month = $request->filled('month') ? Carbon::parse($request->input('month')) : null;
        $year = $request->integer('year') ?: null;

        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        $leaveRequests = LeaveRequest::query()
            ->with(['employee.user', 'leaveType', 'approver'])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($leaveTypeId, fn ($query) => $query->where('leave_type_id', $leaveTypeId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($month, fn ($query) => $query->forMonth($month))
            ->when($year, fn ($query) => $query->whereYear('start_date', $year))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('hr.leaves.index', [
            'activeTab' => 'all',
            'leaveRequests' => $leaveRequests,
            'leaveTypes' => $leaveTypes,
            'filters' => [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'status' => $status,
                'month' => $request->input('month'),
                'year' => $year,
            ],
            'calendar' => null,
            'monthOptions' => collect(range(now()->year - 1, now()->year + 1)),
        ]);
    }

    public function calendar(Request $request): View
    {
        abort_unless(auth()->user()->hasPermission('hr.view'), 403);

        $selectedMonth = $request->filled('month')
            ? Carbon::parse($request->input('month'))->startOfMonth()
            : now()->startOfMonth();

        $leaveTypes = LeaveType::active()->orderBy('name')->get();
        $approvedRequests = LeaveRequest::query()
            ->with(['employee.user', 'leaveType'])
            ->approved()
            ->forMonth($selectedMonth)
            ->orderBy('start_date')
            ->get();

        $requestsByDate = [];
        foreach ($approvedRequests as $leaveRequest) {
            $rangeStart = $leaveRequest->start_date->copy()->max($selectedMonth->copy()->startOfMonth());
            $rangeEnd = $leaveRequest->end_date->copy()->min($selectedMonth->copy()->endOfMonth());

            foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $date) {
                $requestsByDate[$date->toDateString()][] = $leaveRequest;
            }
        }

        $startOfMonth = $selectedMonth->copy()->startOfMonth();
        $endOfMonth = $selectedMonth->copy()->endOfMonth();
        $cursor = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endCursor = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);
        $calendarWeeks = [];
        $week = [];

        while ($cursor->lte($endCursor)) {
            $week[] = [
                'date' => $cursor->copy(),
                'in_month' => $cursor->month === $selectedMonth->month,
                'requests' => $requestsByDate[$cursor->toDateString()] ?? [],
            ];

            if (count($week) === 7) {
                $calendarWeeks[] = $week;
                $week = [];
            }

            $cursor->addDay();
        }

        return view('hr.leaves.index', [
            'activeTab' => 'calendar',
            'leaveRequests' => null,
            'leaveTypes' => $leaveTypes,
            'filters' => [],
            'calendar' => [
                'selectedMonth' => $selectedMonth,
                'weeks' => $calendarWeeks,
                'requestsByDate' => $requestsByDate,
            ],
            'monthOptions' => collect(range(now()->year - 1, now()->year + 1)),
        ]);
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $leaveRequest->approve(auth()->user());

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $leaveRequest->reject(auth()->user(), $validated['rejection_reason']);

        return back()->with('success', 'Leave request rejected.');
    }

    public function updateActualDays(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'days_actual' => ['required', 'numeric', 'min:0.1'],
        ]);

        if ($leaveRequest->status !== 'approved') {
            return back()->with('error', 'Actual days can only be updated for approved requests.');
        }

        $previous = (float) ($leaveRequest->days_actual ?? $leaveRequest->days_requested);
        $actual = (float) $validated['days_actual'];
        $difference = $actual - $previous;

        $balance = EmployeeLeaveBalance::getOrCreate(
            (int) $leaveRequest->employee_id,
            (int) $leaveRequest->leave_type_id,
            (int) $leaveRequest->start_date->year
        );

        if ($difference !== 0.0) {
            $balance->update([
                'used_days' => max(0, (float) $balance->used_days + $difference),
            ]);
        }

        $leaveRequest->update(['days_actual' => $actual]);

        return back()->with('success', 'Actual leave days updated.');
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:leave_requests,id'],
        ]);

        LeaveRequest::whereIn('id', $validated['ids'])
            ->pending()
            ->get()
            ->each(fn (LeaveRequest $leaveRequest) => $leaveRequest->approve(auth()->user()));

        return back()->with('success', 'Selected leave requests approved.');
    }

    public function bulkReject(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:leave_requests,id'],
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        LeaveRequest::whereIn('id', $validated['ids'])
            ->pending()
            ->get()
            ->each(fn (LeaveRequest $leaveRequest) => $leaveRequest->reject(auth()->user(), $validated['rejection_reason']));

        return back()->with('success', 'Selected leave requests rejected.');
    }
}
