<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EmployeeLeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leaveService) {}

    public function store(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        $this->authorizeEmployeeAccess($employee);

        $validated = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $leaveType = LeaveType::findOrFail((int) $validated['leave_type_id']);
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $workingDays = $this->leaveService->calculateWorkingDays($startDate, $endDate);

        $overlapExists = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->exists();

        if ($overlapExists) {
            return back()->withInput()->withErrors(['start_date' => 'This leave overlaps with an existing request.']);
        }

        $year = (int) $startDate->year;
        $balance = $employee->getLeaveBalance($leaveType->id, $year);
        $warning = null;

        if ($leaveType->is_paid && $balance->remaining_days < $workingDays) {
            $warning = 'Requested leave exceeds the available balance. It was still submitted for review.';
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_requested' => $workingDays,
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $leaveType->requires_approval ? 'pending' : 'approved',
        ]);

        $managers = \App\Models\User::query()
            ->whereHas('roleModel', fn ($query) => $query->whereIn('slug', ['manager', 'super-admin']))
            ->get();

        Notification::send($managers, new LeaveRequestSubmittedNotification($leaveRequest->fresh(['employee.user', 'leaveType'])));
        SlackNotificationHelper::notifyOnce(new LeaveRequestSubmittedNotification($leaveRequest->fresh(['employee.user', 'leaveType'])));

        if (!$leaveType->requires_approval) {
            $leaveRequest->approve(auth()->user());
        }

        $redirect = redirect()->to(route('employees.show', $employee) . '#leaves')->with('success', 'Leave request submitted.');

        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function cancel(Request $request, EmployeeProfile $employee, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeEmployeeAccess($employee);

        abort_if(
            !auth()->user()->hasPermission('hr.manage') && (int) $leaveRequest->employee_id !== (int) $employee->id,
            403
        );

        $leaveRequest->cancel();

        return back()->with('success', 'Leave request cancelled.');
    }

    private function authorizeEmployeeAccess(EmployeeProfile $employee): void
    {
        if (auth()->user()->hasPermission('hr.manage')) {
            return;
        }

        abort_unless(auth()->user()->employeeProfile?->id === $employee->id, 403);
    }
}
