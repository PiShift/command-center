<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        Gate::authorize('manageCategories', Expense::class);

        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('expense-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        Gate::authorize('manageCategories', Expense::class);

        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:expense_categories,name',
            'color'      => 'nullable|string|max:20',
            'icon'       => 'nullable|string|max:10',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = ExpenseCategory::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['id' => $category->id, 'name' => $category->name]);
        }

        return redirect()->route('expenses.monthly-overview', ['tab' => 'categories'])->with('success', 'Category created.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        Gate::authorize('manageCategories', Expense::class);

        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
            'color'      => 'nullable|string|max:20',
            'icon'       => 'nullable|string|max:10',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $expenseCategory->update($validated);

        return redirect()->route('expenses.monthly-overview', ['tab' => 'categories'])->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        Gate::authorize('manageCategories', Expense::class);

        if ($expenseCategory->is_system) {
            return redirect()->route('expenses.monthly-overview', ['tab' => 'categories'])->with('error', 'Cannot delete a system category.');
        }

        $expenseCategory->delete();

        return redirect()->route('expenses.monthly-overview', ['tab' => 'categories'])->with('success', 'Category deleted.');
    }
}
