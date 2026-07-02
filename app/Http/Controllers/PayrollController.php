<?php

namespace App\Http\Controllers;

use App\Models\CompanyBankAccount;
use App\Models\EmployeeContract;
use App\Models\EmployeeProfile;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(): View
    {
        $runs = PayrollRun::query()
            ->withCount('entries')
            ->orderByDesc('month')
            ->paginate(20);

        $lastPaidRun = PayrollRun::query()
            ->paid()
            ->orderByDesc('month')
            ->first();

        $totalPaidThisYear = (float) PayrollRun::query()
            ->paid()
            ->whereBetween('paid_at', [now()->startOfYear(), now()->endOfYear()])
            ->sum('total_net');

        $nextPayrollDue = now()->startOfMonth()->addMonth();

        return view('payroll.index', compact('runs', 'lastPaidRun', 'totalPaidThisYear', 'nextPayrollDue'));
    }

    public function create(Request $request): View
    {
        $selectedMonthNumber = (int) $request->input('month', now()->month);
        $selectedYearNumber = (int) $request->input('year', now()->year);

        $selectedMonthNumber = max(1, min(12, $selectedMonthNumber));
        $selectedYearNumber = max(2020, min(now()->year + 2, $selectedYearNumber));

        $selectedMonth = Carbon::create($selectedYearNumber, $selectedMonthNumber, 1)->startOfMonth()->toDateString();

        try {
            $normalizedMonth = Carbon::parse($selectedMonth)->startOfMonth()->toDateString();
        } catch (\Throwable) {
            $normalizedMonth = now()->startOfMonth()->toDateString();
            $selectedMonth = $normalizedMonth;
            $selectedMonthNumber = (int) Carbon::parse($normalizedMonth)->month;
            $selectedYearNumber = (int) Carbon::parse($normalizedMonth)->year;
        }

        $payrollExistsForMonth = PayrollRun::query()
            ->where('month', $normalizedMonth)
            ->exists();

        $activeContractSnapshots = EmployeeContract::query()
            ->where('status', 'active')
            ->select(['employee_id', 'base_salary'])
            ->get();

        $activeEmployeesPreview = $activeContractSnapshots
            ->pluck('employee_id')
            ->unique()
            ->count();

        $estimatedTotal = (float) $activeContractSnapshots->sum(fn (EmployeeContract $contract) => (float) $contract->base_salary);

        return view('payroll.create', compact(
            'selectedMonth',
            'selectedMonthNumber',
            'selectedYearNumber',
            'payrollExistsForMonth',
            'activeEmployeesPreview',
            'estimatedTotal'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date'],
            'month_number' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year_number' => ['nullable', 'integer', 'min:2020', 'max:'.(now()->year + 2)],
        ]);

        $month = null;

        if (! empty($validated['month'])) {
            $month = Carbon::parse($validated['month']);
        } elseif (! empty($validated['month_number']) && ! empty($validated['year_number'])) {
            $month = Carbon::create((int) $validated['year_number'], (int) $validated['month_number'], 1);
        }

        if (! $month) {
            return back()->withInput()->with('error', 'Please select a valid payroll month.');
        }

        try {
            $run = $this->payrollService->generateRun($month);
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('payroll.show', $run)
            ->with('success', 'Payroll run generated successfully.');
    }

    public function show(PayrollRun $run): View
    {
        $run->load([
            'entries.employee.user',
            'entries.contract',
            'entries.advances',
            'entries.loanRepayments.loan',
            'payments.companyAccount',
            'payments.creator',
            'payments.items.entry.employee.user',
            'companyAccount',
            'creator',
        ]);

        $companyAccounts = CompanyBankAccount::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name', 'account_number', 'is_default']);

        $skippedEmployees = EmployeeProfile::query()
            ->active()
            ->whereDoesntHave('contracts', fn ($query) => $query->where('status', 'active'))
            ->with('user')
            ->get();

        return view('payroll.show', compact('run', 'companyAccounts', 'skippedEmployees'));
    }

    public function approve(PayrollRun $run): RedirectResponse
    {
        try {
            $this->payrollService->approveRun($run);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Payroll run approved successfully.');
    }

    public function pay(Request $request, PayrollRun $run): RedirectResponse
    {
        $validated = $request->validate([
            'company_account_id' => ['required', 'integer', 'exists:company_bank_accounts,id'],
            'payroll_entry_ids' => ['required', 'array', 'min:1'],
            'payroll_entry_ids.*' => ['required', 'integer'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->payrollService->paySelected(
                $run,
                (int) $validated['company_account_id'],
                $validated['payroll_entry_ids'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Selected employees have been marked as paid.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        if ($run->status !== 'draft') {
            return back()->with('error', 'Only draft payroll runs can be deleted.');
        }

        $run->delete();

        return redirect()->route('payroll.index')->with('success', 'Payroll run deleted.');
    }

    public function updateEntry(Request $request, PayrollRun $run, PayrollEntry $entry)
    {
        if ($run->status !== 'draft') {
            return back()->with('error', 'Entries can only be adjusted while payroll run is draft.');
        }

        if ((int) $entry->payroll_run_id !== (int) $run->id) {
            return back()->with('error', 'The selected entry does not belong to this payroll run.');
        }

        $validated = $request->validate([
            'bonuses' => ['required', 'numeric', 'min:0'],
            'other_deductions' => ['required', 'numeric', 'min:0'],
            'skip_advances' => ['nullable', 'boolean'],
            'skip_loans' => ['nullable', 'boolean'],
            'skip_unpaid_leave' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $bonuses = (float) $validated['bonuses'];
        $otherDeductions = (float) $validated['other_deductions'];
        $grossAmount = (float) $entry->base_salary + $bonuses;
        $skipAdvances = (bool) ($validated['skip_advances'] ?? false);
        $skipLoans = (bool) ($validated['skip_loans'] ?? false);
        $skipUnpaidLeave = (bool) ($validated['skip_unpaid_leave'] ?? false);
        $unpaidLeaveDeduction = (float) $entry->unpaid_leave_deduction;
        $effectiveOtherDeductions = max(0, $otherDeductions - ($skipUnpaidLeave ? $unpaidLeaveDeduction : 0));

        $netAmount = $grossAmount
            - ($skipAdvances ? 0 : (float) $entry->advances_deducted)
            - ($skipLoans ? 0 : (float) $entry->loans_deducted)
            - $effectiveOtherDeductions;

        $entry->update([
            'bonuses' => $bonuses,
            'other_deductions' => $otherDeductions,
            'skip_advances' => $skipAdvances,
            'skip_loans' => $skipLoans,
            'skip_unpaid_leave' => $skipUnpaidLeave,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'notes' => $validated['notes'] ?? null,
        ]);

        $run->recalculateTotals();

        if ($request->expectsJson()) {
            $entry->refresh();
            $run->refresh();

            return new JsonResponse([
                'entry' => [
                    'id' => $entry->id,
                    'gross_amount' => (float) $entry->gross_amount,
                    'net_amount' => (float) $entry->net_amount,
                    'bonuses' => (float) $entry->bonuses,
                    'other_deductions' => (float) $entry->other_deductions,
                    'skip_advances' => (bool) $entry->skip_advances,
                    'skip_loans' => (bool) $entry->skip_loans,
                    'skip_unpaid_leave' => (bool) $entry->skip_unpaid_leave,
                    'unpaid_leave_deduction' => (float) $entry->unpaid_leave_deduction,
                    'notes' => $entry->notes,
                ],
                'run' => [
                    'total_gross' => (float) $run->total_gross,
                    'total_deductions' => (float) $run->total_deductions,
                    'total_net' => (float) $run->total_net,
                ],
            ]);
        }

        return back()->with('success', 'Payroll entry updated.');
    }

    public function pdf(PayrollRun $run)
    {
        $run->load([
            'entries.employee.user',
            'entries.contract',
            'entries.advances',
            'entries.loanRepayments.loan',
            'creator',
        ]);

        $logoPath = null;
        $logoFile = public_path('images/logo.png');
        if (file_exists($logoFile)) {
            $logoPath = 'data:image/png;base64,'.base64_encode(file_get_contents($logoFile));
        }

        $pdf = Pdf::loadView('payroll.pdf', [
            'run' => $run,
            'logoPath' => $logoPath,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('payroll-'.$run->month->format('Y-m').'.pdf');
    }
}
