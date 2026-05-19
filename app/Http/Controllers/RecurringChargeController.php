<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\RecurringCharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecurringChargeController extends Controller
{
    public function index()
    {
        Gate::authorize('manageRecurring', Expense::class);

        $charges = RecurringCharge::with(['category', 'project'])
            ->orderBy('name')
            ->get();

        return view('recurring-charges.index', compact('charges'));
    }

    public function create()
    {
        Gate::authorize('manageRecurring', Expense::class);

        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        $projects   = Project::orderBy('name')->get();

        return view('recurring-charges.create', compact('categories', 'projects'));
    }

    public function store(Request $request)
    {
        Gate::authorize('manageRecurring', Expense::class);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'category_id'      => 'nullable|exists:expense_categories,id',
            'project_id'       => 'nullable|exists:projects,id',
            'amount'           => 'required|numeric|min:0',
            'frequency'        => 'required|in:monthly,quarterly,annual',
            'start_date'       => 'required|date',
            'next_due_date'    => 'required|date',
            'end_date'         => 'nullable|date',
            'max_occurrences'  => 'nullable|integer|min:1',
            'is_active'        => 'nullable|boolean',
            'notes'            => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        // Currency is always MRU
        $validated['currency'] = 'MRU';

        RecurringCharge::create($validated);

        return redirect()->route('recurring-charges.index')->with('success', 'Recurring charge created.');
    }

    public function show(RecurringCharge $recurringCharge)
    {
        Gate::authorize('manageRecurring', Expense::class);

        return redirect()->route('recurring-charges.edit', $recurringCharge);
    }

    public function edit(RecurringCharge $recurringCharge)
    {
        Gate::authorize('manageRecurring', Expense::class);

        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        $projects   = Project::orderBy('name')->get();

        return view('recurring-charges.edit', compact('recurringCharge', 'categories', 'projects'));
    }

    public function update(Request $request, RecurringCharge $recurringCharge)
    {
        Gate::authorize('manageRecurring', Expense::class);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'category_id'      => 'nullable|exists:expense_categories,id',
            'project_id'       => 'nullable|exists:projects,id',
            'amount'           => 'required|numeric|min:0',
            'frequency'        => 'required|in:monthly,quarterly,annual',
            'start_date'       => 'required|date',
            'next_due_date'    => 'required|date',
            'end_date'         => 'nullable|date',
            'max_occurrences'  => 'nullable|integer|min:1',
            'is_active'        => 'nullable|boolean',
            'notes'            => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['currency']  = 'MRU';

        $recurringCharge->update($validated);

        $redirectTo = $request->input('_redirect', 'expenses.monthly-overview');
        if ($redirectTo === 'expenses.monthly-overview') {
            return redirect()->route('expenses.monthly-overview', ['tab' => 'recurring'])->with('success', 'Recurring charge updated.');
        }
        return redirect()->route('recurring-charges.index')->with('success', 'Recurring charge updated.');
    }

    public function destroy(RecurringCharge $recurringCharge)
    {
        Gate::authorize('manageRecurring', Expense::class);

        $recurringCharge->delete();

        return redirect()->route('recurring-charges.index')->with('success', 'Recurring charge deleted.');
    }
}
