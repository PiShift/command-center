<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('hr.view'), 403);

        $query = EmployeeProfile::with(['user', 'contracts' => fn ($q) => $q->where('status', 'active')->limit(1)])
            ->orderBy('employee_number');

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($type = $request->employment_type) {
            $query->where('employment_type', $type);
        }

        $employees = $query->paginate(50)->withQueryString();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $users = User::whereDoesntHave('employeeProfile')->orderBy('name')->get(['id', 'name', 'email']);

        return view('employees.form', [
            'employee' => null,
            'users'    => $users,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'user_id'                  => 'required|exists:users,id|unique:employee_profiles,user_id',
            'job_title'                => 'nullable|string|max:255',
            'department'               => 'nullable|string|max:255',
            'employment_type'          => 'required|in:CDI,CDD,freelance,internship,part_time',
            'status'                   => 'nullable|in:active,on_leave,terminated',
            'start_date'               => 'required|date',
            'end_date'                 => 'nullable|date|after:start_date',
            'personal_phone'           => 'nullable|string|max:50',
            'personal_email'           => 'nullable|email|max:255',
            'address'                  => 'nullable|string',
            'emergency_contact_name'   => 'nullable|string|max:255',
            'emergency_contact_phone'  => 'nullable|string|max:50',
            'notes'                    => 'nullable|string',
            'nni'                      => 'nullable|string|max:50',
            'date_of_birth'            => 'nullable|date',
            'nationality'              => 'nullable|string|max:100',
            'category'                 => 'nullable|string|max:100',
            'work_location'            => 'nullable|string|max:255',
            'probation_period_months'  => 'nullable|integer|min:0|max:24',
            'supervisor_name'          => 'nullable|string|max:255',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';

        $employee = EmployeeProfile::create($validated);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee profile created.');
    }

    public function show(EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.view'), 403);

        $employee->load([
            'user',
            'contracts' => fn ($q) => $q->with('template')
                ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'draft' THEN 2 WHEN 'terminated' THEN 3 ELSE 4 END")
                ->orderByDesc('effective_from'),
            'documents',
            'bankAccounts' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id'),
        ]);

        return view('employees.show', compact('employee'));
    }

    public function uploadAvatar(Request $request, EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $employee->clearMediaCollection('avatar');
        $employee->addMediaFromRequest('avatar')
                 ->toMediaCollection('avatar');

        return redirect()->back()->with('success', 'Avatar updated.');
    }

    public function edit(EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $users = User::where('id', $employee->user_id)
            ->orWhereDoesntHave('employeeProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('employees.form', compact('employee', 'users'));
    }

    public function update(Request $request, EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'user_id'                  => 'required|exists:users,id|unique:employee_profiles,user_id,' . $employee->id,
            'job_title'                => 'nullable|string|max:255',
            'department'               => 'nullable|string|max:255',
            'employment_type'          => 'required|in:CDI,CDD,freelance,internship,part_time',
            'status'                   => 'required|in:active,on_leave,terminated',
            'start_date'               => 'required|date',
            'end_date'                 => 'nullable|date|after:start_date',
            'personal_phone'           => 'nullable|string|max:50',
            'personal_email'           => 'nullable|email|max:255',
            'address'                  => 'nullable|string',
            'emergency_contact_name'   => 'nullable|string|max:255',
            'emergency_contact_phone'  => 'nullable|string|max:50',
            'notes'                    => 'nullable|string',
            'nni'                      => 'nullable|string|max:50',
            'date_of_birth'            => 'nullable|date',
            'nationality'              => 'nullable|string|max:100',
            'category'                 => 'nullable|string|max:100',
            'work_location'            => 'nullable|string|max:255',
            'probation_period_months'  => 'nullable|integer|min:0|max:24',
            'supervisor_name'          => 'nullable|string|max:255',
        ]);

        $employee->update($validated);

        if ($request->hasFile('_avatar')) {
            $request->validate(['_avatar' => 'image|max:5120']);
            $employee->addMediaFromRequest('_avatar')->toMediaCollection('avatar');
        }

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee profile updated.');
    }

    public function destroy(EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee removed.');
    }
}
