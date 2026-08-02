<?php

namespace App\Http\Controllers;

use App\Models\BankAccountTransfer;
use App\Models\CompanyBankAccount;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\Expense;
use App\Models\PayrollRun;
use App\Models\PayrollRunPayment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
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
                    (float) $account->balance
                );
            });

        return view('finance.bank-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('finance.manage'), 403);

        return view('finance.bank-accounts.form', [
            'account' => new CompanyBankAccount,
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
                        .($payment->invoice?->invoice_number ?? 'N/A')
                        .' ('.($payment->invoice?->customer?->name ?? 'Unknown customer').')',
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
                    'description' => $expense->title.' ('.($expense->category?->name ?? 'Uncategorized').')',
                    'amount' => (float) $expense->amount,
                    'reference' => null,
                    'source' => 'expense',
                    'source_id' => $expense->id,
                ]);
            });

        // Advances given (OUT)
        EmployeeAdvance::where('company_account_id', $account->id)
            ->with('employee.user')
            ->get()
            ->each(function ($advance) use (&$transactions) {
                $employeeName = $advance->employee?->user?->name ?? 'Unknown employee';

                $transactions->push([
                    'date' => $advance->date,
                    'type' => 'out',
                    'description' => 'Advance — '.$employeeName.' ('.$advance->reason.')',
                    'amount' => (float) $advance->amount,
                    'reference' => null,
                    'source' => 'advance',
                    'source_id' => $advance->id,
                ]);
            });

        // Loans disbursed (OUT)
        EmployeeLoan::where('company_account_id', $account->id)
            ->with('employee.user')
            ->get()
            ->each(function ($loan) use (&$transactions) {
                $employeeName = $loan->employee?->user?->name ?? 'Unknown employee';

                $transactions->push([
                    'date' => $loan->started_at,
                    'type' => 'out',
                    'description' => 'Loan — '.$employeeName.' ('.$loan->title.')',
                    'amount' => (float) $loan->amount_total,
                    'reference' => null,
                    'source' => 'loan',
                    'source_id' => $loan->id,
                ]);
            });

        if (Schema::hasTable('payroll_run_payments')) {
            PayrollRunPayment::query()
                ->where('company_account_id', $account->id)
                ->with(['run', 'items'])
                ->get()
                ->each(function (PayrollRunPayment $payment) use (&$transactions) {
                    $runMonthLabel = $payment->run?->month
                        ? Carbon::parse($payment->run->month)->format('F Y')
                        : 'Unknown month';

                    $transactions->push([
                        'date' => $payment->paid_at,
                        'type' => 'out',
                        'description' => 'Payroll batch — '
                            .$runMonthLabel
                            .' ('.$payment->items->count().' employees)',
                        'amount' => (float) $payment->amount,
                        'reference' => $payment->reference,
                        'source' => 'payroll_run_payment',
                        'source_id' => $payment->id,
                    ]);
                });
        } else {
            PayrollRun::where('company_account_id', $account->id)
                ->where('status', 'paid')
                ->with('entries')
                ->get()
                ->each(function ($run) use (&$transactions) {
                    $transactions->push([
                        'date' => $run->paid_at,
                        'type' => 'out',
                        'description' => 'Payroll — '
                            .Carbon::parse($run->month)->format('F Y')
                            .' ('.$run->entries->count().' employees)',
                        'amount' => (float) $run->total_net,
                        'reference' => null,
                        'source' => 'payroll_run',
                        'source_id' => $run->id,
                    ]);
                });
        }

        if (Schema::hasTable('bank_account_transfers')) {
            // Transfers IN
            BankAccountTransfer::where('to_account_id', $account->id)
                ->with(['fromAccount', 'toAccount'])
                ->get()
                ->each(function ($transfer) use (&$transactions, $account) {
                    $isCrossCurrency = ! is_null($transfer->exchange_rate)
                        && (float) ($transfer->amount_received ?? 0) > 0
                        && strtoupper((string) $transfer->fromAccount?->currency) !== strtoupper((string) $account->currency);

                    $description = 'Transfer from '.($transfer->fromAccount?->name ?? 'Unknown account');
                    if ($isCrossCurrency) {
                        $description .= ' — '.$this->formatCrossCurrencySummary($transfer);
                    }

                    $transactions->push([
                        'date' => $transfer->date,
                        'type' => 'in',
                        'description' => $description,
                        'amount' => (float) ($transfer->amount_received ?? $transfer->amount),
                        'reference' => $transfer->reference,
                        'source' => 'transfer_in',
                        'source_id' => $transfer->id,
                        'can_delete' => $transfer->date?->isToday() ?? false,
                    ]);
                });

            // Transfers OUT
            BankAccountTransfer::where('from_account_id', $account->id)
                ->with(['fromAccount', 'toAccount'])
                ->get()
                ->each(function ($transfer) use (&$transactions, $account) {
                    $isCrossCurrency = ! is_null($transfer->exchange_rate)
                        && (float) ($transfer->amount_received ?? 0) > 0
                        && strtoupper((string) $account->currency) !== strtoupper((string) $transfer->toAccount?->currency);

                    $description = 'Transfer to '.($transfer->toAccount?->name ?? 'Unknown account');
                    if ($isCrossCurrency) {
                        $description .= ' — '.$this->formatCrossCurrencySummary($transfer);
                    }

                    $transactions->push([
                        'date' => $transfer->date,
                        'type' => 'out',
                        'description' => $description,
                        'amount' => (float) ($transfer->amount_sent ?? $transfer->amount),
                        'reference' => $transfer->reference,
                        'source' => 'transfer_out',
                        'source_id' => $transfer->id,
                        'can_delete' => $transfer->date?->isToday() ?? false,
                    ]);
                });
        }

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
        if (Schema::hasTable('bank_account_transfers')) {
            $totalIn += (float) BankAccountTransfer::query()
                ->where('to_account_id', $account->id)
                ->selectRaw('COALESCE(SUM(COALESCE(amount_received, amount)), 0) as total')
                ->value('total');
        }

        $totalOut = (float) $account->expenses()->where('status', 'confirmed')->sum('amount')
            + (float) EmployeeAdvance::where('company_account_id', $account->id)->sum('amount')
            + (float) EmployeeLoan::where('company_account_id', $account->id)->sum('amount_total')
            + (Schema::hasTable('payroll_run_payments')
                ? (float) PayrollRunPayment::where('company_account_id', $account->id)->sum('amount')
                : (float) PayrollRun::where('company_account_id', $account->id)->where('status', 'paid')->sum('total_net'));

        if (Schema::hasTable('bank_account_transfers')) {
            $totalOut += (float) BankAccountTransfer::query()
                ->where('from_account_id', $account->id)
                ->selectRaw('COALESCE(SUM(COALESCE(amount_sent, amount)), 0) as total')
                ->value('total');
        }
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

    private function formatCrossCurrencySummary(BankAccountTransfer $transfer): string
    {
        $sent = $this->formatTransferMoney(
            (float) ($transfer->amount_sent ?? $transfer->amount ?? 0),
            (string) ($transfer->fromAccount?->currency ?? 'MRU')
        );
        $received = $this->formatTransferMoney(
            (float) ($transfer->amount_received ?? $transfer->amount ?? 0),
            (string) ($transfer->toAccount?->currency ?? 'MRU')
        );
        $rate = rtrim(rtrim(number_format((float) $transfer->exchange_rate, 6, '.', ''), '0'), '.');

        return $sent.' → '.$received.' (rate: '.$rate.')';
    }

    private function formatTransferMoney(float $amount, string $currency): string
    {
        $formatted = number_format($amount, 2);
        $upperCurrency = strtoupper($currency);

        if ($upperCurrency === 'USD') {
            return '$'.$formatted.' USD';
        }

        return $formatted.' '.$upperCurrency;
    }
}
