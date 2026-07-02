<?php

namespace App\Services;

use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\PayrollRunPayment;
use App\Models\PayrollRunPaymentEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
    public function __construct(private readonly LeaveService $leaveService) {}

    public function generateRun(Carbon $month): PayrollRun
    {
        $monthStart = $month->copy()->startOfMonth();

        if (PayrollRun::query()->where('month', $monthStart->toDateString())->exists()) {
            throw new RuntimeException('Payroll run already exists for this month.');
        }

        $run = PayrollRun::create([
            'month' => $monthStart->toDateString(),
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        $employees = EmployeeProfile::query()
            ->active()
            ->with(['contracts' => fn ($query) => $query->where('status', 'active')->orderByDesc('effective_from')])
            ->get();

        foreach ($employees as $employee) {
            $contract = $employee->contracts->first();

            if (! $contract) {
                continue;
            }

            $baseSalary = (float) $contract->base_salary;
            $advancesDeducted = (float) EmployeeAdvance::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->sum('amount');

            $activeLoans = EmployeeLoan::query()
                ->where('employee_id', $employee->id)
                ->active()
                ->get();

            $loansDeducted = (float) $activeLoans->sum(fn (EmployeeLoan $loan) => $loan->calculateNextInstallment($baseSalary));
            $workingDaysInMonth = max(1, $this->leaveService->calculateWorkingDays($month->copy()->startOfMonth(), $month->copy()->endOfMonth()));
            $unpaidLeaveDays = (float) LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->approved()
                ->whereHas('leaveType', fn ($query) => $query->where('is_paid', false))
                ->forMonth($month)
                ->sum(DB::raw('COALESCE(days_actual, days_requested, 0)'));

            $dailyRate = $baseSalary / $workingDaysInMonth;
            $unpaidLeaveDeduction = round($unpaidLeaveDays * $dailyRate, 2);
            $otherDeductions = $unpaidLeaveDeduction;
            $bonuses = 0.0;
            $grossAmount = $baseSalary + $bonuses;
            $netAmount = $grossAmount - $advancesDeducted - $loansDeducted - $otherDeductions;

            PayrollEntry::create([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'contract_id' => $contract->id,
                'base_salary' => $baseSalary,
                'gross_amount' => $grossAmount,
                'advances_deducted' => $advancesDeducted,
                'loans_deducted' => $loansDeducted,
                'other_deductions' => $otherDeductions,
                'unpaid_leave_deduction' => $unpaidLeaveDeduction,
                'skip_unpaid_leave' => false,
                'bonuses' => $bonuses,
                'net_amount' => $netAmount,
            ]);
        }

        $run->recalculateTotals();

        return $run;
    }

    public function approveRun(PayrollRun $run): void
    {
        if ($run->status !== 'draft') {
            throw new RuntimeException('Only draft payroll runs can be approved.');
        }

        DB::transaction(function () use ($run) {
            $entries = $run->entries()->get();

            foreach ($entries as $entry) {
                if ($entry->skip_advances) {
                    $entry->advances_deducted = 0;
                }

                if ($entry->skip_loans) {
                    $entry->loans_deducted = 0;
                }

                $otherDeductions = (float) $entry->other_deductions;
                if ($entry->skip_unpaid_leave) {
                    $otherDeductions -= (float) $entry->unpaid_leave_deduction;
                }

                $entry->net_amount = (float) $entry->gross_amount
                    - (float) $entry->advances_deducted
                    - (float) $entry->loans_deducted
                    - max(0, $otherDeductions);
                $entry->save();

                if (! $entry->skip_advances) {
                    EmployeeAdvance::query()
                        ->where('employee_id', $entry->employee_id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'deducted',
                            'payroll_entry_id' => $entry->id,
                        ]);
                }

                if (! $entry->skip_loans) {
                    $activeLoansList = EmployeeLoan::query()
                        ->where('employee_id', $entry->employee_id)
                        ->active()
                        ->get();

                    $salary = (float) $entry->base_salary;

                    foreach ($activeLoansList as $loan) {
                        $installment = (float) $loan->calculateNextInstallment($salary);
                        $remaining = (float) $loan->amount_remaining;
                        $amount = min($installment, $remaining);

                        if ($amount <= 0) {
                            continue;
                        }

                        EmployeeLoanRepayment::create([
                            'loan_id' => $loan->id,
                            'payroll_entry_id' => $entry->id,
                            'amount' => $amount,
                            'salary_snapshot' => $salary,
                            'percentage_snapshot' => $loan->repayment_type === 'percentage'
                                ? $loan->repayment_value
                                : null,
                            'repayment_date' => now(),
                        ]);

                        if ($loan->fresh()->isFullyRepaid()) {
                            $loan->update([
                                'status' => 'completed',
                                'ended_at' => today(),
                            ]);
                        }
                    }
                }
            }

            $run->update(['status' => 'approved']);
            $run->recalculateTotals();
        });
    }

    public function payRun(PayrollRun $run, int $companyAccountId): void
    {
        $pendingEntryIds = $run->entries()
            ->where('status', 'pending')
            ->pluck('id')
            ->all();

        if ($pendingEntryIds === []) {
            throw new RuntimeException('No pending payroll entries to pay.');
        }

        $this->paySelected($run, $companyAccountId, $pendingEntryIds);
    }

    /**
     * @param  list<int|string>  $entryIds
     */
    public function paySelected(PayrollRun $run, int $companyAccountId, array $entryIds, ?string $reference = null, ?string $notes = null): void
    {
        if (! in_array($run->status, ['approved', 'partially_paid'], true)) {
            throw new RuntimeException('Only approved or partially paid payroll runs can be paid.');
        }

        $normalizedEntryIds = collect($entryIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($normalizedEntryIds === []) {
            throw new RuntimeException('Select at least one payroll entry to pay.');
        }

        DB::transaction(function () use ($run, $companyAccountId, $normalizedEntryIds, $reference, $notes) {
            $run = PayrollRun::query()->lockForUpdate()->findOrFail($run->id);

            if (! in_array($run->status, ['approved', 'partially_paid'], true)) {
                throw new RuntimeException('Only approved or partially paid payroll runs can be paid.');
            }

            $entries = PayrollEntry::query()
                ->where('payroll_run_id', $run->id)
                ->whereIn('id', $normalizedEntryIds)
                ->lockForUpdate()
                ->get();

            if ($entries->count() !== count($normalizedEntryIds)) {
                throw new RuntimeException('Some selected payroll entries are invalid for this run.');
            }

            if ($entries->contains(fn (PayrollEntry $entry) => $entry->status !== 'pending')) {
                throw new RuntimeException('Only pending payroll entries can be paid.');
            }

            $now = now();
            $batchAmount = (float) $entries->sum('net_amount');

            if ($batchAmount <= 0) {
                throw new RuntimeException('Selected payroll entries must have a positive payable amount.');
            }

            $paymentBatch = PayrollRunPayment::query()->create([
                'payroll_run_id' => $run->id,
                'company_account_id' => $companyAccountId,
                'amount' => $batchAmount,
                'paid_at' => $now,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            $entries->each(function (PayrollEntry $entry) use ($paymentBatch): void {
                PayrollRunPaymentEntry::query()->create([
                    'payroll_run_payment_id' => $paymentBatch->id,
                    'payroll_entry_id' => $entry->id,
                    'amount' => (float) $entry->net_amount,
                ]);
            });

            PayrollEntry::query()
                ->whereIn('id', $entries->pluck('id')->all())
                ->update([
                    'status' => 'paid',
                    'paid_at' => $now,
                ]);

            $hasPendingEntries = PayrollEntry::query()
                ->where('payroll_run_id', $run->id)
                ->where('status', 'pending')
                ->exists();

            $distinctAccounts = PayrollRunPayment::query()
                ->where('payroll_run_id', $run->id)
                ->select('company_account_id')
                ->distinct()
                ->pluck('company_account_id')
                ->filter(fn ($id) => $id !== null)
                ->values();

            $runStatus = $hasPendingEntries ? 'partially_paid' : 'paid';
            $singleAccountForRun = (! $hasPendingEntries && $distinctAccounts->count() === 1)
                ? (int) $distinctAccounts->first()
                : null;

            $run->update([
                'status' => $runStatus,
                'paid_at' => $hasPendingEntries ? null : $now,
                'company_account_id' => $singleAccountForRun,
            ]);

            $run->recalculateTotals();
        });
    }
}
