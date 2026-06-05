<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBankAccount;
use App\Models\EmployeeProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeBankAccountController extends Controller
{
    public function store(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['is_primary'] = (bool) ($validated['is_primary'] ?? false);

        EmployeeBankAccount::create($validated);

        return back()->with('success', 'Bank account added.');
    }

    public function update(Request $request, EmployeeProfile $employee, EmployeeBankAccount $account): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($account->employee_id === $employee->id, 404);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_primary'] = (bool) ($validated['is_primary'] ?? false);

        $account->update($validated);

        return back()->with('success', 'Bank account updated.');
    }

    public function destroy(EmployeeProfile $employee, EmployeeBankAccount $account): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($account->employee_id === $employee->id, 404);
        abort_if($employee->bankAccounts()->count() <= 1, 403);

        $account->delete();

        return back()->with('success', 'Bank account deleted.');
    }

    public function setPrimary(EmployeeProfile $employee, EmployeeBankAccount $account): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($account->employee_id === $employee->id, 404);

        $account->update(['is_primary' => true]);

        return back()->with('success', 'Primary account updated.');
    }
}
