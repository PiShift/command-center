<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use App\Models\EmployeeContract;
use App\Models\EmployeeProfile;
use App\Services\HrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeContractController extends Controller
{
    public function create(EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $templates = ContractTemplate::active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'employment_type', 'is_default']);

        return view('employees.contracts.create', compact('employee', 'templates'));
    }

    public function store(Request $request, EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        // Determine intent first — draft saves anything, activate enforces all constraints
        $action  = $request->input('action', 'draft');
        $isDraft = $action === 'draft';

        $req = $isDraft ? 'nullable' : 'required';

        $validated = $request->validate([
            'template_id'           => 'nullable|exists:contract_templates,id',
            'employment_type'       => $req . '|in:CDI,CDD,freelance,internship,part_time',
            'base_salary'           => $req . '|numeric|min:0',
            'working_hours_per_day' => $req . '|numeric|min:1|max:24',
            'working_days_per_week' => $req . '|integer|min:1|max:7',
            'notice_period_days'    => $req . '|integer|min:0',
            'effective_from'        => $req . '|date',
            'effective_to'          => 'nullable|date',
            'additional_clauses'    => 'nullable|string',
            'action'                => 'nullable|in:draft,activate',
        ]);

        unset($validated['action']);

        // For draft, default employment_type to the employee's own type
        $validated['employment_type'] ??= $employee->employment_type;

        // Template guard — only enforced when activating
        if (! $isDraft && empty($validated['template_id'])) {
            $hasTemplate = ContractTemplate::active()
                ->forType($validated['employment_type'])
                ->exists();

            if (! $hasTemplate) {
                return back()
                    ->withInput()
                    ->withErrors(['template_id' => 'No contract template exists for the "' . $validated['employment_type'] . '" type. Create a template first.']);
            }
        }

        $contract = $employee->contracts()->create(array_merge($validated, [
            'currency' => 'MRU',
            'status'   => 'draft',
        ]));

        if ($action === 'activate') {
            $contract->activate();
        }

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Contract ' . ($action === 'activate' ? 'created and activated' : 'saved as draft') . '.');
    }

    public function edit(EmployeeProfile $employee, EmployeeContract $contract)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($contract->employee_id === $employee->id, 404);
        abort_unless($contract->status === 'draft', 403);

        $templates = ContractTemplate::active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'employment_type', 'is_default']);

        return view('employees.contracts.create', compact('employee', 'contract', 'templates'));
    }

    public function update(Request $request, EmployeeProfile $employee, EmployeeContract $contract)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($contract->employee_id === $employee->id, 404);
        abort_unless($contract->status === 'draft', 403);

        $action  = $request->input('action', 'draft');
        $isDraft = $action === 'draft';
        $req     = $isDraft ? 'nullable' : 'required';

        $validated = $request->validate([
            'template_id'           => 'nullable|exists:contract_templates,id',
            'employment_type'       => $req . '|in:CDI,CDD,freelance,internship,part_time',
            'base_salary'           => $req . '|numeric|min:0',
            'working_hours_per_day' => $req . '|numeric|min:1|max:24',
            'working_days_per_week' => $req . '|integer|min:1|max:7',
            'notice_period_days'    => $req . '|integer|min:0',
            'effective_from'        => $req . '|date',
            'effective_to'          => 'nullable|date',
            'additional_clauses'    => 'nullable|string',
            'action'                => 'nullable|in:draft,activate',
        ]);

        unset($validated['action']);
        $validated['employment_type'] ??= $contract->employment_type ?? $employee->employment_type;

        if (! $isDraft && empty($validated['template_id'])) {
            $hasTemplate = ContractTemplate::active()
                ->forType($validated['employment_type'])
                ->exists();

            if (! $hasTemplate) {
                return back()
                    ->withInput()
                    ->withErrors(['template_id' => 'No contract template exists for the "' . $validated['employment_type'] . '" type. Create a template first.']);
            }
        }

        $contract->update($validated);

        if ($action === 'activate') {
            $contract->activate();
        }

        return redirect()->route('employees.show', $employee)
            ->with('success', $action === 'activate' ? 'Contract updated and activated.' : 'Draft updated.');
    }

    public function download(EmployeeProfile $employee, EmployeeContract $contract, HrService $hrService)
    {
        abort_unless(auth()->user()->hasPermission('hr.view'), 403);
        abort_unless($contract->employee_id === $employee->id, 404);

        return $hrService->generateContract($contract);
    }

    public function activate(EmployeeProfile $employee, EmployeeContract $contract)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($contract->employee_id === $employee->id, 404);

        // Specific field validation as required
        if (! ($contract->base_salary > 0)) {
            return back()->withErrors(['activate' => 'Salary must be greater than 0.']);
        }
        if (is_null($contract->effective_from)) {
            return back()->withErrors(['activate' => 'Effective date is required.']);
        }
        if (! $contract->employment_type) {
            return back()->withErrors(['activate' => 'Contract type is required.']);
        }

        // Template guard
        if (! $contract->template_id) {
            $hasTemplate = ContractTemplate::active()
                ->forType($contract->employment_type)
                ->exists();

            if (! $hasTemplate) {
                return back()->withErrors(['activate' => 'No contract template exists for "' . $contract->employment_type . '". Create a template first.']);
            }
        }

        $previousContract = null;
        DB::transaction(function () use ($contract, $employee, &$previousContract) {
            $previousContract = EmployeeContract::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->first();

            if ($previousContract) {
                $previousContract->update([
                    'status'       => 'terminated',
                    'effective_to' => $contract->effective_from->subDay(),
                ]);
            }

            $contract->update(['status' => 'active']);
        });

        $message = $previousContract
            ? "Contract activated. Previous contract ({$previousContract->contract_reference}) has been closed."
            : 'Contract activated successfully.';

        return back()->with('success', $message);
    }

    public function uploadSigned(Request $request, EmployeeProfile $employee, EmployeeContract $contract)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($contract->employee_id === $employee->id, 404);

        $request->validate([
            'signed_contract' => 'required|file|mimes:pdf|max:10240',
        ]);

        $contract->addMediaFromRequest('signed_contract')
            ->toMediaCollection('signed_contract');

        return back()->with('success', 'Signed contract uploaded.');
    }
}
