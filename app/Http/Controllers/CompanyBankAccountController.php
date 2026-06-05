<?php

namespace App\Http\Controllers;

use App\Models\CompanyBankAccount;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class CompanyBankAccountController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);

        $accounts = CompanyBankAccount::query()
            ->withSum('invoicePayments as payments_in_sum', 'amount')
            ->withSum([
                'expenses as expenses_out_sum' => fn ($query) => $query->where('status', 'confirmed'),
            ], 'amount')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->each(function (CompanyBankAccount $account) {
                $account->setAttribute(
                    'computed_balance',
                    (float) ($account->payments_in_sum ?? 0) - (float) ($account->expenses_out_sum ?? 0)
                );
            });

        return view('finance.bank-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);

        return view('finance.bank-accounts.form', [
            'account' => new CompanyBankAccount(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

        if ($validated['is_default']) {
            CompanyBankAccount::query()->update(['is_default' => false]);
        }

        CompanyBankAccount::create($validated);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account created.');
    }

    public function show(CompanyBankAccount $account): View
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);

        $transactions = collect();

        $account->invoicePayments()
            ->with('invoice.customer')
            ->get()
            ->each(function ($payment) use (&$transactions) {
                $transactions->push([
                    'date' => $payment->payment_date,
                    'type' => 'in',
                    'description' => 'Payment received — '
                        . ($payment->invoice?->invoice_number ?? 'N/A')
                        . ' (' . ($payment->invoice?->customer?->name ?? 'Unknown customer') . ')',
                    'amount' => (float) $payment->amount,
                    'reference' => $payment->reference,
                    'source' => 'invoice_payment',
                    'source_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                ]);
            });

        Expense::where('company_account_id', $account->id)
            ->where('status', 'confirmed')
            ->with('category')
            ->get()
            ->each(function ($expense) use (&$transactions) {
                $transactions->push([
                    'date' => $expense->expense_date,
                    'type' => 'out',
                    'description' => $expense->title . ' (' . ($expense->category?->name ?? 'Uncategorized') . ')',
                    'amount' => (float) $expense->amount,
                    'reference' => null,
                    'source' => 'expense',
                    'source_id' => $expense->id,
                ]);
            });

        $transactions = $transactions
            ->sortByDesc('date')
            ->values();

        $perPage = 25;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $transactions->slice(($page - 1) * $perPage, $perPage)->values();

        $transactions = new LengthAwarePaginator(
            $items,
            $transactions->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $totalIn = (float) $account->invoicePayments()->sum('amount');
        $totalOut = (float) $account->expenses()->where('status', 'confirmed')->sum('amount');
        $currentBalance = $totalIn - $totalOut;

        // Opening balance before oldest row on this page, so view can compute running balance.
        $olderTransactions = $transactions->slice($page * $perPage);
        $openingBalance = (float) $olderTransactions->reduce(function ($carry, $transaction) {
            return $carry
                + ($transaction['type'] === 'in'
                    ? (float) $transaction['amount']
                    : -1 * (float) $transaction['amount']);
        }, 0.0);

        return view('finance.bank-accounts.show', compact(
            'account',
            'transactions',
            'totalIn',
            'totalOut',
            'currentBalance',
            'openingBalance'
        ));
    }

    public function edit(CompanyBankAccount $account): View
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);

        return view('finance.bank-accounts.form', compact('account'));
    }

    public function update(Request $request, CompanyBankAccount $account): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

        if ($validated['is_default']) {
            CompanyBankAccount::query()->where('id', '!=', $account->id)->update(['is_default' => false]);
        }

        $account->update($validated);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account updated.');
    }

    public function destroy(CompanyBankAccount $account): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);
        abort_if($account->is_system, 403);
        abort_if($account->invoicePayments()->exists() || $account->expenses()->exists(), 403);

        $account->delete();

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account deleted.');
    }
}
