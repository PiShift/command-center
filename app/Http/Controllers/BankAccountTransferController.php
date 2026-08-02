<?php

namespace App\Http\Controllers;

use App\Models\BankAccountTransfer;
use App\Models\CompanyBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BankAccountTransferController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('finance.manage'), 403);

        if (!Schema::hasTable('bank_account_transfers')) {
            return back()->with('error', 'Transfers table is missing. Please run migrations for bank account transfers.');
        }

        $validated = $request->validate([
            'from_account_id' => ['required', 'integer', 'exists:company_bank_accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:company_bank_accounts,id', 'different:from_account_id'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'amount_sent' => ['nullable', 'numeric', 'gt:0'],
            'amount_received' => ['nullable', 'numeric', 'gt:0'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $fromAccount = CompanyBankAccount::query()->findOrFail((int) $validated['from_account_id']);
        $toAccount = CompanyBankAccount::query()->findOrFail((int) $validated['to_account_id']);
        $isCrossCurrency = strtoupper((string) $fromAccount->currency) !== strtoupper((string) $toAccount->currency);

        $supportsCrossCurrencyPair = collect([$fromAccount->currency, $toAccount->currency])
            ->map(fn (?string $currency): string => strtoupper((string) $currency))
            ->sort()
            ->values()
            ->all() === ['MRU', 'USD'];

        if ($isCrossCurrency && ! $supportsCrossCurrencyPair) {
            return back()->withErrors([
                'exchange_rate' => 'Cross-currency transfers currently support only MRU ↔ USD.',
            ])->withInput();
        }

        if ($isCrossCurrency) {
            if (
                ! isset($validated['amount_sent'], $validated['amount_received'])
                || (float) $validated['amount_sent'] <= 0
                || (float) $validated['amount_received'] <= 0
            ) {
                return back()->withErrors([
                    'amount_sent' => 'Amount sent and amount received are required for cross-currency transfers.',
                ])->withInput();
            }
        } elseif (! isset($validated['amount']) || (float) $validated['amount'] <= 0) {
            return back()->withErrors([
                'amount' => 'Amount is required for same-currency transfers.',
            ])->withInput();
        }

        $amountSent = $isCrossCurrency
            ? (float) $validated['amount_sent']
            : (float) ($validated['amount'] ?? 0);
        $amountReceived = $isCrossCurrency
            ? (float) $validated['amount_received']
            : $amountSent;
        $exchangeRate = $isCrossCurrency
            ? (float) ($validated['exchange_rate'] ?? $this->resolveUsdExchangeRateDefault($fromAccount, $toAccount))
            : null;

        $fromBalance = (float) $fromAccount->balance;
        $willBeNegative = $fromBalance < $amountSent;

        BankAccountTransfer::create([
            'from_account_id' => (int) $validated['from_account_id'],
            'to_account_id' => (int) $validated['to_account_id'],
            'amount' => $amountSent,
            'amount_sent' => $amountSent,
            'amount_received' => $amountReceived,
            'exchange_rate' => $exchangeRate,
            'date' => $validated['date'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $message = 'Transfer recorded successfully.';
        if ($willBeNegative) {
            $message .= ' Warning: source account balance is now negative.';
        }

        return redirect()->route('bank-accounts.index')->with('success', $message);
    }

    public function destroy(BankAccountTransfer $transfer): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('finance.manage'), 403);

        if (!Schema::hasTable('bank_account_transfers')) {
            return back()->with('error', 'Transfers table is missing. Please run migrations for bank account transfers.');
        }

        if (!$transfer->date || !$transfer->date->isToday()) {
            return back()->with('error', 'Only transfers created for today can be deleted.');
        }

        $transfer->delete();

        return back()->with('success', 'Transfer deleted successfully.');
    }

    private function resolveUsdExchangeRateDefault(CompanyBankAccount $fromAccount, CompanyBankAccount $toAccount): float
    {
        $usdAccount = strtoupper((string) $fromAccount->currency) === 'USD'
            ? $fromAccount
            : (strtoupper((string) $toAccount->currency) === 'USD' ? $toAccount : null);

        if (! $usdAccount || is_null($usdAccount->usd_exchange_rate) || (float) $usdAccount->usd_exchange_rate <= 0) {
            return 40.0;
        }

        return (float) $usdAccount->usd_exchange_rate;
    }
}
