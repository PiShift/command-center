<?php

namespace App\Services;

use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeeProfile;
use App\Models\LeaveAccrualLog;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public function initializeBalancesForEmployee(EmployeeProfile $employee, int $year): void
    {
        $contract = $employee->current_contract;
        $contractStart = $contract?->effective_from ?? $employee->start_date ?? now();
        $activeTypes = LeaveType::active()->whereNotNull('default_days_per_year')->get();

        foreach ($activeTypes as $leaveType) {
            $balance = EmployeeLeaveBalance::getOrCreate($employee->id, $leaveType->id, $year);

            if ($leaveType->accrues_monthly) {
                $monthsWorked = $this->monthsWorkedInYear($contractStart, $year);
                $initialDays = round($monthsWorked * (float) $leaveType->monthly_accrual_days, 1);

                $balance->update([
                    'allocated_days' => max((float) $balance->allocated_days, $initialDays),
                ]);
            } else {
                $balance->update([
                    'allocated_days' => max((float) $balance->allocated_days, (float) $leaveType->default_days_per_year),
                ]);
            }
        }
    }

    public function accrueMonthlyLeave(Carbon $month): void
    {
        $month = $month->copy()->startOfMonth();
        $activeTypes = LeaveType::active()->where('accrues_monthly', true)->get();
        $employees = EmployeeProfile::query()
            ->active()
            ->with(['contracts' => fn ($query) => $query->where('status', 'active')->orderByDesc('effective_from')])
            ->get();

        DB::transaction(function () use ($month, $activeTypes, $employees) {
            foreach ($employees as $employee) {
                $contract = $employee->contracts->first();
                if (!$contract) {
                    continue;
                }

                foreach ($activeTypes as $leaveType) {
                    $exists = LeaveAccrualLog::query()
                        ->where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $month->year)
                        ->where('month', $month->month)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $balance = EmployeeLeaveBalance::getOrCreate($employee->id, $leaveType->id, $month->year);
                    $balance->increment('allocated_days', (float) $leaveType->monthly_accrual_days);

                    LeaveAccrualLog::create([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $month->year,
                        'month' => $month->month,
                        'days_accrued' => (float) $leaveType->monthly_accrual_days,
                        'accrued_at' => now(),
                    ]);
                }
            }
        });
    }

    public function calculateWorkingDays(Carbon $start, Carbon $end): float
    {
        $days = 0;

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            if ($date->isWeekday()) {
                $days++;
            }
        }

        return (float) $days;
    }

    public function carryOverBalances(int $year): void
    {
        $maxCarryOver = (float) config('leave.max_carry_over', 5);
        $previousYearBalances = EmployeeLeaveBalance::query()
            ->where('year', $year)
            ->whereHas('leaveType', fn ($query) => $query->where('code', 'annual'))
            ->with('leaveType')
            ->get();

        DB::transaction(function () use ($previousYearBalances, $year, $maxCarryOver) {
            foreach ($previousYearBalances as $previousBalance) {
                $remaining = max(0, (float) $previousBalance->allocated_days + (float) $previousBalance->carried_over_days - (float) $previousBalance->used_days);
                $carryOver = min($remaining, $maxCarryOver);
                $nextYear = $year + 1;

                EmployeeLeaveBalance::getOrCreate(
                    (int) $previousBalance->employee_id,
                    (int) $previousBalance->leave_type_id,
                    $nextYear
                )->update([
                    'carried_over_days' => $carryOver,
                ]);
            }
        });
    }

    private function monthsWorkedInYear(Carbon $contractStart, int $year): int
    {
        $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
        $effectiveStart = $contractStart->greaterThan($startOfYear) ? $contractStart->copy()->startOfMonth() : $startOfYear;
        $endOfYear = $year === (int) now()->year ? now()->endOfMonth() : Carbon::create($year, 12, 31)->endOfDay();

        if ($effectiveStart->gt($endOfYear)) {
            return 0;
        }

        return (int) $effectiveStart->diffInMonths($endOfYear) + 1;
    }
}
