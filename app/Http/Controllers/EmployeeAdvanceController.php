<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAdvance;
use App\Models\EmployeeProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeAdvanceController extends Controller
{
    public function store(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'company_account_id' => 'required|exists:company_bank_accounts,id',
            'amount' => 'required|numeric|gt:0',
            'date' => 'required|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        EmployeeAdvance::create([
            ...$validated,
            'employee_id' => $employee->id,
            'recorded_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()
            ->to(route('employees.show', $employee) . '#advances')
            ->with('success', 'Advance recorded.');
    }

    public function update(Request $request, EmployeeProfile $employee, EmployeeAdvance $advance): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($advance->employee_id === $employee->id, 404);

        $validated = $request->validate([
            'company_account_id' => 'required|exists:company_bank_accounts,id',
            'amount' => 'required|numeric|gt:0',
            'date' => 'required|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $advance->update($validated);

        return redirect()
            ->to(route('employees.show', $employee) . '#advances')
            ->with('success', 'Advance updated.');
    }

    public function destroy(EmployeeProfile $employee, EmployeeAdvance $advance): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($advance->employee_id === $employee->id, 404);

        abort_if($advance->status === 'deducted', 403);

        $advance->delete();

        return redirect()
            ->to(route('employees.show', $employee) . '#advances')
            ->with('success', 'Advance deleted.');
    }
}
