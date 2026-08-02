<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use App\Services\HrService;
use App\Models\EmployeeProfile;
use App\Models\EmployeeContract;

class ContractTemplateController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $templates = ContractTemplate::orderByDesc('is_default')
            ->orderBy('employment_type')
            ->orderBy('name')
            ->get();

        return view('hr.contract-templates.index', compact('templates'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $template     = new ContractTemplate();
        $placeholders = (new ContractTemplate())->getAvailablePlaceholders();

        return view('hr.contract-templates.form', compact('template', 'placeholders'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'employment_type' => 'required|in:CDI,CDD,freelance,internship,all',
            'content'         => 'required|string',
            'language'        => 'required|in:fr,ar,en',
            'version'         => 'required|string|max:20',
            'is_default'      => 'boolean',
            'is_active'       => 'boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active']  = $request->boolean('is_active');

        if ($validated['is_default']) {
            // Unset any existing default for this employment type
            ContractTemplate::where('employment_type', $validated['employment_type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        ContractTemplate::create($validated);

        return redirect()->route('contract-templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(ContractTemplate $contractTemplate): View
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $template     = $contractTemplate;
        $placeholders = $contractTemplate->getAvailablePlaceholders();

        return view('hr.contract-templates.form', compact('template', 'placeholders'));
    }

    public function update(Request $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'employment_type' => 'required|in:CDI,CDD,freelance,internship,all',
            'content'         => 'required|string',
            'language'        => 'required|in:fr,ar,en',
            'version'         => 'required|string|max:20',
            'is_default'      => 'boolean',
            'is_active'       => 'boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active']  = $request->boolean('is_active');

        if ($validated['is_default']) {
            ContractTemplate::where('employment_type', $validated['employment_type'])
                ->where('is_default', true)
                ->where('id', '!=', $contractTemplate->id)
                ->update(['is_default' => false]);
        }

        $contractTemplate->update($validated);

        return redirect()->route('contract-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(ContractTemplate $contractTemplate): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $contractTemplate->delete();

        return redirect()->route('contract-templates.index')
            ->with('success', 'Template deleted.');
    }

    /**
     * Generate a PDF preview using dummy data.
     */
    public function preview(Request $request, ContractTemplate $contractTemplate, HrService $hrService): Response
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        // Build a dummy contract so we can pass it through the replacement map
        $dummyEmployee = new EmployeeProfile([
            'employee_number'        => 'EMP-001',
            'job_title'              => 'Développeur Senior',
            'department'             => 'Ingénierie',
            'employment_type'        => $contractTemplate->employment_type === 'all' ? 'CDI' : $contractTemplate->employment_type,
            'nni'                    => '1234567890',
            'nationality'            => 'Mauritanienne',
            'date_of_birth'          => now()->subYears(28)->toDateString(),
            'work_location'          => 'Nouakchott',
            'category'               => 'M5',
            'probation_period_months'=> 2,
        ]);
        $dummyEmployee->id = 0;

        // Fake user relation
        $dummyUser       = new \App\Models\User(['name' => 'Ahmed Ould Mohamed']);
        $dummyEmployee->setRelation('user', $dummyUser);

        $dummyContract = new EmployeeContract([
            'employment_type'       => $dummyEmployee->employment_type,
            'base_salary'           => 150000,
            'currency'              => 'MRU',
            'working_hours_per_day' => 8,
            'working_days_per_week' => 5,
            'notice_period_days'    => 30,
            'effective_from'        => now()->toDateString(),
            'effective_to'          => null,
            'contract_reference'    => 'CTR-' . now()->year . '-PREVIEW',
            'status'                => 'draft',
        ]);
        $dummyContract->id = 0;
        $dummyContract->setRelation('employee', $dummyEmployee);
        $dummyContract->setRelation('template', $contractTemplate);

        $dompdf   = $hrService->generateContractPdf($dummyContract);
        $filename = 'preview-' . str($contractTemplate->name)->slug() . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
