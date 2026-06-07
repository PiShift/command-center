<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $leaveTypes = LeaveType::query()->orderBy('name')->get();

        return view('hr.leave-types.index', compact('leaveTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:leave_types,code'],
            'color' => ['required', 'string', 'max:32'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'accrues_monthly' => ['nullable', 'boolean'],
            'monthly_accrual_days' => ['nullable', 'numeric', 'min:0'],
            'default_days_per_year' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_paid'] = (bool) ($validated['is_paid'] ?? true);
        $validated['requires_approval'] = (bool) ($validated['requires_approval'] ?? true);
        $validated['accrues_monthly'] = (bool) ($validated['accrues_monthly'] ?? false);
        $validated['monthly_accrual_days'] = (float) ($validated['monthly_accrual_days'] ?? 0);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        LeaveType::create($validated);

        return back()->with('success', 'Leave type created.');
    }

    public function update(Request $request, LeaveType $type): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:leave_types,code,' . $type->id],
            'color' => ['required', 'string', 'max:32'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'accrues_monthly' => ['nullable', 'boolean'],
            'monthly_accrual_days' => ['nullable', 'numeric', 'min:0'],
            'default_days_per_year' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($type->is_system && $validated['code'] !== $type->code) {
            return back()->with('error', 'System leave type codes cannot be changed.');
        }

        $validated['is_paid'] = (bool) ($validated['is_paid'] ?? false);
        $validated['requires_approval'] = (bool) ($validated['requires_approval'] ?? true);
        $validated['accrues_monthly'] = (bool) ($validated['accrues_monthly'] ?? false);
        $validated['monthly_accrual_days'] = (float) ($validated['monthly_accrual_days'] ?? 0);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $type->update($validated);

        return back()->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $type): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_if($type->is_system, 403, 'System leave types cannot be deleted.');

        if ($type->leaveRequests()->exists()) {
            return back()->with('error', 'Leave type cannot be deleted because it has existing requests.');
        }

        $type->delete();

        return back()->with('success', 'Leave type deleted.');
    }
}
