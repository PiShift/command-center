<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MonthlyBudget;
use App\Models\RecurringCharge;
use Carbon\Carbon;

class ExpenseService
{
    /**
     * Generate draft expenses for all active recurring charges due in the given month.
     * Skips charges that already have a draft for that month.
     *
     * @return int Number of drafts created
     */
    public function generateRecurringDrafts(Carbon $month): int
    {
        $start = $month->copy()->startOfMonth();
        $count = 0;

        $today = Carbon::today();

        RecurringCharge::active()->dueInMonth($month)
            ->with(['category'])
            ->get()
            ->each(function (RecurringCharge $charge) use ($start, $today, &$count) {
                // Stop: end_date has passed
                if ($charge->end_date && $charge->end_date->lt($today)) {
                    $charge->update(['is_active' => false]);
                    return;
                }

                // Stop: max occurrences reached
                if ($charge->max_occurrences !== null && $charge->occurrences_count >= $charge->max_occurrences) {
                    $charge->update(['is_active' => false]);
                    return;
                }

                // Skip if a draft already exists for this charge + month
                $exists = Expense::where('recurring_charge_id', $charge->id)
                    ->where('month', $start->toDateString())
                    ->exists();

                if ($exists) {
                    return;
                }

                Expense::create([
                    'title'               => $charge->name,
                    'category_id'         => $charge->category_id,
                    'project_id'          => $charge->project_id,
                    'recurring_charge_id' => $charge->id,
                    'amount'              => $charge->amount,
                    'expense_date'        => $charge->next_due_date->toDateString(),
                    'status'              => 'draft',
                ]);

                $charge->increment('occurrences_count');
                $charge->computeNextDueDate();
                $count++;
            });

        return $count;
    }

    /**
     * Confirm a draft expense.
     */
    public function confirmExpense(Expense $expense): void
    {
        $expense->status = 'confirmed';
        $expense->save();

        activity()
            ->performedOn($expense)
            ->log('Expense confirmed: ' . $expense->title);
    }

    /**
     * Returns a structured monthly summary per category.
     */
    public function getMonthlySummary(Carbon $month): array
    {
        $startOfMonth = $month->copy()->startOfMonth()->toDateString();
        $categories   = ExpenseCategory::orderBy('sort_order')->get();
        $budgets      = MonthlyBudget::getForMonth($month);

        $rows   = [];
        $totals = [
            'budget_amount'    => 0,
            'recurring_amount' => 0,
            'actual_amount'    => 0,
            'expected_amount'  => 0,
            'variance'         => 0,
        ];

        foreach ($categories as $category) {
            $budgetAmount = (float) ($budgets->get($category->id)?->amount ?? 0);

            $recurringAmount = Expense::where('category_id', $category->id)
                ->where('month', $startOfMonth)
                ->whereNotNull('recurring_charge_id')
                ->sum('amount');

            $actualAmount = Expense::where('category_id', $category->id)
                ->where('month', $startOfMonth)
                ->where('status', 'confirmed')
                ->sum('amount');

            $recurringAmount = (float) $recurringAmount;
            $actualAmount    = (float) $actualAmount;
            $hasExpectation  = $budgetAmount > 0 || $recurringAmount > 0;
            $hasActual       = $actualAmount > 0;

            // State C: nothing to show — skip
            if (!$hasExpectation && !$hasActual) {
                continue;
            }

            $expectedTotal = $budgetAmount + $recurringAmount;

            // State A: planned (has budget or recurring)
            // State B: unplanned (actual spend with no budget/recurring)
            if ($hasExpectation) {
                $state    = 'planned';
                $variance = $expectedTotal - $actualAmount;
            } else {
                $state    = 'unplanned';
                $variance = 0;
            }

            $rows[] = [
                'category'         => $category,
                'budget_amount'    => $budgetAmount,
                'recurring_amount' => $recurringAmount,
                'actual_amount'    => $actualAmount,
                'expected_total'   => $expectedTotal,
                'variance'         => $variance,
                'state'            => $state,
            ];

            $totals['budget_amount']    += $budgetAmount;
            $totals['recurring_amount'] += $recurringAmount;
            $totals['actual_amount']    += $actualAmount;
            $totals['expected_amount']  += $expectedTotal;
            $totals['variance']         += $variance;
        }

        $drafts = Expense::where('month', $startOfMonth)
            ->where('status', 'draft')
            ->with(['category', 'recurringCharge'])
            ->orderBy('expense_date')
            ->get();

        return compact('rows', 'totals', 'drafts');
    }
}
