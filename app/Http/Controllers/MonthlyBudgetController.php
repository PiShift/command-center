<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MonthlyBudget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MonthlyBudgetController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manageBudgets', Expense::class);

        $month      = $request->month ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth() : Carbon::now()->startOfMonth();
        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        $budgets    = MonthlyBudget::getForMonth($month);

        return view('monthly-budgets.index', compact('categories', 'budgets', 'month'));
    }

    public function store(Request $request)
    {
        Gate::authorize('manageBudgets', Expense::class);

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'month'       => 'required|date',
            'amount'      => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        // Always store as first day of month
        $validated['month']    = Carbon::parse($validated['month'])->startOfMonth()->toDateString();
        $validated['currency'] = 'MRU';

        MonthlyBudget::updateOrCreate(
            ['category_id' => $validated['category_id'], 'month' => $validated['month']],
            ['amount' => $validated['amount'], 'currency' => $validated['currency'], 'notes' => $validated['notes'] ?? null]
        );

        return back()->with('success', 'Budget saved.');
    }

    public function destroy(MonthlyBudget $budget)
    {
        Gate::authorize('manageBudgets', Expense::class);

        $budget->delete();

        return back()->with('success', 'Budget removed.');
    }
}
