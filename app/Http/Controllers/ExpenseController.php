<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\CompanyBankAccount;
use App\Models\Project;
use App\Models\RecurringCharge;
use App\Services\ExpenseService;
use App\Services\UsdCostBasisService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Expense::class);

        $query = Expense::with(['category', 'project', 'recurringCharge'])->orderByDesc('expense_date');

        if ($search = $request->search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($categoryId = $request->category_id) {
            $query->where('category_id', $categoryId);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($month = $request->month) {
            $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $query->where('month', $monthDate);
        }

        if ($sourceInvoice = $request->source_invoice) {
            $itemIds = \App\Models\InvoiceItem::where('invoice_id', $sourceInvoice)->pluck('id');
            $query->whereIn('source_invoice_item_id', $itemIds);
        }

        $expenses   = $query->paginate(50)->withQueryString();
        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        $drafts    = Expense::where('status', 'draft')->with(['category'])->orderByDesc('expense_date')->get();
        $confirmed = Expense::where('status', 'confirmed')->with(['category'])->orderByDesc('expense_date');

        return view('expenses.index', compact('expenses', 'categories', 'drafts'));
    }

    public function create()
    {
        Gate::authorize('create', Expense::class);

        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        $projects   = Project::orderBy('name')->get();
        $companyAccounts = CompanyBankAccount::orderByDesc('is_default')->orderBy('name')->get();
        $defaultCompanyAccountId = CompanyBankAccount::where('is_system', true)
            ->where('name', 'Cash')
            ->value('id');

        return view('expenses.create', compact('categories', 'projects', 'companyAccounts', 'defaultCompanyAccountId'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Expense::class);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'nullable|exists:expense_categories,id',
            'project_id'     => 'nullable|exists:projects,id',
            'company_account_id' => 'required|exists:company_bank_accounts,id',
            'amount'         => 'required|numeric|min:0',
            'expense_date'   => 'required|date',
            'status'         => 'nullable|in:draft,confirmed',
            'notes'          => 'nullable|string',
            'is_recurring'        => 'nullable|boolean',
            'rec_frequency'       => 'nullable|in:monthly,quarterly,annual',
            'rec_start_date'      => 'nullable|date',
            'rec_end_date'        => 'nullable|date',
            'rec_max_occurrences' => 'nullable|integer|min:1',
        ]);

        $isRecurring     = $request->boolean('is_recurring');
        $recFrequency    = $validated['rec_frequency'] ?? 'monthly';
        $recStartDate    = $validated['rec_start_date'] ?? $validated['expense_date'];
        $recEndDate      = $validated['rec_end_date'] ?? null;
        $recMaxOccurrences = $validated['rec_max_occurrences'] ?? null;

        unset($validated['is_recurring'], $validated['rec_frequency'], $validated['rec_start_date'], $validated['rec_end_date'], $validated['rec_max_occurrences']);
        $companyAccount = CompanyBankAccount::query()->findOrFail((int) $validated['company_account_id']);
        $this->applyExpenseCurrencyValues($validated, $companyAccount);

        $expense = Expense::create($validated);

        if ($request->hasFile('receipt')) {
            $expense->addMediaFromRequest('receipt')
                ->toMediaCollection('receipt');
        }

        if ($isRecurring) {
            $charge = \App\Models\RecurringCharge::create([
                'name'            => $expense->title,
                'category_id'     => $expense->category_id,
                'project_id'      => $expense->project_id,
                'amount'          => $expense->amount,
                'frequency'       => $recFrequency,
                'start_date'      => $recStartDate,
                'next_due_date'   => $recStartDate,
                'end_date'        => $recEndDate,
                'max_occurrences' => $recMaxOccurrences,
                'is_active'       => true,
                'currency'        => 'MRU',
            ]);
            $expense->update(['recurring_charge_id' => $charge->id]);
        }

        $this->recalculateUsdCostBasisForAccounts([$companyAccount]);

        return redirect()->route('expenses.monthly-overview')->with('success', 'Expense created.');
    }

    public function edit(Expense $expense)
    {
        Gate::authorize('update', $expense);

        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        $projects   = Project::orderBy('name')->get();
        $companyAccounts = CompanyBankAccount::orderByDesc('is_default')->orderBy('name')->get();
        $defaultCompanyAccountId = CompanyBankAccount::where('is_system', true)
            ->where('name', 'Cash')
            ->value('id');

        return view('expenses.edit', compact('expense', 'categories', 'projects', 'companyAccounts', 'defaultCompanyAccountId'));
    }

    public function update(Request $request, Expense $expense)
    {
        Gate::authorize('update', $expense);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'nullable|exists:expense_categories,id',
            'project_id'     => 'nullable|exists:projects,id',
            'company_account_id' => 'required|exists:company_bank_accounts,id',
            'amount'         => 'required|numeric|min:0',
            'expense_date'   => 'required|date',
            'status'         => 'nullable|in:draft,confirmed',
            'notes'          => 'nullable|string',
            'is_recurring'        => 'nullable|boolean',
            'rec_frequency'       => 'nullable|in:monthly,quarterly,annual',
            'rec_start_date'      => 'nullable|date',
            'rec_end_date'        => 'nullable|date',
            'rec_max_occurrences' => 'nullable|integer|min:1',
        ]);

        $isRecurring       = $request->boolean('is_recurring');
        $recFrequency      = $validated['rec_frequency'] ?? 'monthly';
        $recStartDate      = $validated['rec_start_date'] ?? $validated['expense_date'];
        $recEndDate        = $validated['rec_end_date'] ?? null;
        $recMaxOccurrences = $validated['rec_max_occurrences'] ?? null;

        $previousAccount = $expense->companyAccount;
        unset($validated['is_recurring'], $validated['rec_frequency'], $validated['rec_start_date'], $validated['rec_end_date'], $validated['rec_max_occurrences']);
        $companyAccount = CompanyBankAccount::query()->findOrFail((int) $validated['company_account_id']);
        $this->applyExpenseCurrencyValues($validated, $companyAccount);

        $expense->update($validated);

        if ($request->hasFile('receipt')) {
            $expense->addMediaFromRequest('receipt')
                ->toMediaCollection('receipt');
        }

        if ($isRecurring) {
            if ($expense->recurringCharge) {
                $expense->recurringCharge->update([
                    'name'            => $expense->title,
                    'category_id'     => $expense->category_id,
                    'project_id'      => $expense->project_id,
                    'amount'          => $expense->amount,
                    'frequency'       => $recFrequency,
                    'start_date'      => $recStartDate,
                    'end_date'        => $recEndDate,
                    'max_occurrences' => $recMaxOccurrences,
                    'is_active'       => true,
                ]);
            } else {
                $charge = \App\Models\RecurringCharge::create([
                    'name'            => $expense->title,
                    'category_id'     => $expense->category_id,
                    'project_id'      => $expense->project_id,
                    'amount'          => $expense->amount,
                    'frequency'       => $recFrequency,
                    'start_date'      => $recStartDate,
                    'next_due_date'   => $recStartDate,
                    'end_date'        => $recEndDate,
                    'max_occurrences' => $recMaxOccurrences,
                    'is_active'       => true,
                    'currency'        => 'MRU',
                ]);
                $expense->update(['recurring_charge_id' => $charge->id]);
            }
        } else {
            if ($expense->recurringCharge) {
                $expense->recurringCharge->update(['is_active' => false]);
                $expense->update(['recurring_charge_id' => null]);
            }
        }

        $this->recalculateUsdCostBasisForAccounts([$previousAccount, $companyAccount]);

        return redirect()->route('expenses.monthly-overview')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        Gate::authorize('delete', $expense);

        $account = $expense->companyAccount;
        $expense->delete();
        $this->recalculateUsdCostBasisForAccounts([$account]);

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    public function confirm(Request $request, Expense $expense, ExpenseService $service)
    {
        Gate::authorize('confirm', $expense);

        if ($expense->status === 'confirmed') {
            return back()->with('error', 'Expense is already confirmed.');
        }

        $service->confirmExpense($expense);

        return back()->with('success', 'Expense confirmed.');
    }

    public function toggleRecurring(RecurringCharge $recurringCharge)
    {
        Gate::authorize('manageCategories', Expense::class);

        $recurringCharge->update(['is_active' => !$recurringCharge->is_active]);

        $msg = $recurringCharge->is_active ? 'Recurring charge resumed.' : 'Recurring charge stopped.';

        return redirect()->route('expenses.monthly-overview', ['tab' => 'recurring'])->with('success', $msg);
    }

    public function bulkConfirm(Request $request, ExpenseService $service)
    {
        Gate::authorize('confirm', Expense::class);

        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:expenses,id',
        ]);

        $expenses = Expense::whereIn('id', $validated['ids'])->where('status', 'draft')->get();

        foreach ($expenses as $expense) {
            $service->confirmExpense($expense);
        }

        return back()->with('success', $expenses->count() . ' expense(s) confirmed.');
    }

    public function generateDrafts(Request $request, ExpenseService $service)
    {
        Gate::authorize('manageCategories', Expense::class);

        $month = $request->month
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $count = $service->generateRecurringDrafts($month);

        return back()->with('success', "{$count} draft(s) generated for {$month->format('F Y')}.");
    }

    public function monthlyOverview(Request $request, ExpenseService $service)
    {
        Gate::authorize('viewAny', Expense::class);

        $month = $request->month
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $summary = $service->getMonthlySummary($month);

        // Build 6-month trend data
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m       = $month->copy()->subMonths($i);
            $label   = $m->format('M Y');
            $actual  = Expense::forMonth($m)->confirmed()->sum('amount_mru');
            $trend[] = ['label' => $label, 'actual' => (float) $actual];
        }

        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('expenses.monthly-overview', array_merge($summary, compact('month', 'trend', 'categories')));
    }

    private function applyExpenseCurrencyValues(array &$validated, CompanyBankAccount $companyAccount): void
    {
        $currency = strtoupper((string) $companyAccount->currency);
        $amount = (float) $validated['amount'];

        if ($currency === 'USD') {
            $rate = (float) ($companyAccount->usd_weighted_average_rate ?: 0);
            $validated['currency'] = 'USD';
            $validated['exchange_rate_used'] = $rate > 0 ? $rate : 0;
            $validated['amount_mru'] = round($amount * $validated['exchange_rate_used'], 2);

            return;
        }

        $validated['currency'] = 'MRU';
        $validated['exchange_rate_used'] = 1;
        $validated['amount_mru'] = round($amount, 2);
    }

    /**
     * @param  array<int, CompanyBankAccount|null>  $accounts
     */
    private function recalculateUsdCostBasisForAccounts(array $accounts): void
    {
        $service = app(UsdCostBasisService::class);

        collect($accounts)
            ->filter()
            ->unique(fn (CompanyBankAccount $account): int => (int) $account->id)
            ->each(function (CompanyBankAccount $account) use ($service): void {
                if (strtoupper((string) $account->currency) === 'USD') {
                    $service->recalculateForAccount($account);
                }
            });
    }
}
