<?php

namespace App\Services;

use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\EmployeeProfile;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
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

            if (!$contract) {
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
            $otherDeductions = 0.0;
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

                $entry->net_amount = (float) $entry->gross_amount
                    - (float) $entry->advances_deducted
                    - (float) $entry->loans_deducted
                    - (float) $entry->other_deductions;
                $entry->save();

                if (!$entry->skip_advances) {
                    EmployeeAdvance::query()
                        ->where('employee_id', $entry->employee_id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'deducted',
                            'payroll_entry_id' => $entry->id,
                        ]);
                }

                if (!$entry->skip_loans) {
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
        if ($run->status !== 'approved') {
            throw new RuntimeException('Only approved payroll runs can be paid.');
        }

        DB::transaction(function () use ($run, $companyAccountId) {
            $now = now();

            $run->entries()->update([
                'status' => 'paid',
                'paid_at' => $now,
            ]);

            $run->update([
                'status' => 'paid',
                'paid_at' => $now,
                'company_account_id' => $companyAccountId,
            ]);

            $run->recalculateTotals();
        });
    }
}
