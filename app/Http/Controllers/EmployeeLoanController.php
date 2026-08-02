<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLoan;
use App\Models\EmployeeProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeLoanController extends Controller
{
    public function store(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'company_account_id' => 'required|exists:company_bank_accounts,id',
            'title' => 'required|string|max:255',
            'amount_total' => 'required|numeric|gt:0',
            'repayment_type' => 'required|in:fixed_amount,percentage',
            'repayment_value' => 'required|numeric|gt:0',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'notes' => 'nullable|string',
        ]);

        if (($validated['repayment_type'] ?? null) === 'percentage') {
            $request->validate([
                'repayment_value' => 'numeric|between:1,100',
            ]);
        }

        EmployeeLoan::create([
            ...$validated,
            'employee_id' => $employee->id,
            'recorded_by' => auth()->id(),
            'status' => 'active',
        ]);

        return redirect()
            ->to(route('employees.show', $employee) . '#loans')
            ->with('success', 'Loan recorded.');
    }

    public function show(EmployeeProfile $employee, EmployeeLoan $loan)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($loan->employee_id === $employee->id, 404);

        $loan->load([
            'employee.user',
            'companyAccount',
            'recorder',
            'repayments' => fn ($q) => $q->orderBy('repayment_date')->orderBy('id'),
        ]);

        return view('employees.loans.show', compact('employee', 'loan'));
    }

    public function update(Request $request, EmployeeProfile $employee, EmployeeLoan $loan): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($loan->employee_id === $employee->id, 404);

        $validated = $request->validate([
            'company_account_id' => 'required|exists:company_bank_accounts,id',
            'title' => 'required|string|max:255',
            'amount_total' => 'required|numeric|gt:0',
            'repayment_type' => 'required|in:fixed_amount,percentage',
            'repayment_value' => 'required|numeric|gt:0',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'status' => 'required|in:active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (($validated['repayment_type'] ?? null) === 'percentage') {
            $request->validate([
                'repayment_value' => 'numeric|between:1,100',
            ]);
        }

        $loan->update($validated);

        return redirect()
            ->to(route('employees.show', $employee) . '#loans')
            ->with('success', 'Loan updated.');
    }

    public function destroy(EmployeeProfile $employee, EmployeeLoan $loan): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($loan->employee_id === $employee->id, 404);

        $loan->delete();

        return redirect()
            ->to(route('employees.show', $employee) . '#loans')
            ->with('success', 'Loan deleted.');
    }

    public function cancel(EmployeeProfile $employee, EmployeeLoan $loan): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($loan->employee_id === $employee->id, 404);

        $loan->update([
            'status' => 'cancelled',
            'ended_at' => now()->toDateString(),
        ]);

        return redirect()
            ->to(route('employees.show', $employee) . '#loans')
            ->with('success', 'Loan cancelled.');
    }
}
