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
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $fromAccount = CompanyBankAccount::query()->findOrFail((int) $validated['from_account_id']);
        $fromBalance = (float) $fromAccount->balance;
        $transferAmount = (float) $validated['amount'];
        $willBeNegative = $fromBalance < $transferAmount;

        BankAccountTransfer::create([
            'from_account_id' => (int) $validated['from_account_id'],
            'to_account_id' => (int) $validated['to_account_id'],
            'amount' => $transferAmount,
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
}
