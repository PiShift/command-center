<?php

namespace App\Services;

use App\Models\BankAccountTransfer;
use App\Models\CompanyBankAccount;
use App\Models\Expense;
use Illuminate\Support\Collection;

class UsdCostBasisService
{
    public function recalculateForAccount(CompanyBankAccount $account): void
    {
        if (strtoupper((string) $account->currency) !== 'USD') {
            return;
        }

        $events = $this->buildEvents($account);

        $usdBalance = 0.0;
        $costBasisMru = 0.0;
        $weightedAverageRate = 0.0;

        foreach ($events as $event) {
            if ($event['type'] === 'transfer_in') {
                $usdBalance += $event['usd_amount'];
                $costBasisMru += $event['mru_amount'];
                $weightedAverageRate = $usdBalance > 0
                    ? $costBasisMru / $usdBalance
                    : 0.0;

                continue;
            }

            if ($usdBalance <= 0) {
                $costBasisMru = 0.0;
                $weightedAverageRate = 0.0;

                continue;
            }

            $costBasisMru -= $event['usd_amount'] * $weightedAverageRate;
            $usdBalance -= $event['usd_amount'];

            if ($usdBalance <= 0) {
                $usdBalance = 0.0;
                $costBasisMru = 0.0;
                $weightedAverageRate = 0.0;
            }
        }

        $account->update([
            'usd_cost_basis_mru' => max(0, $costBasisMru),
            'usd_weighted_average_rate' => $usdBalance > 0
                ? max(0, $costBasisMru / $usdBalance)
                : 0,
        ]);
    }

    /**
     * @return Collection<int, array{type: string, usd_amount: float, mru_amount: float, date: string, id: int}>
     */
    private function buildEvents(CompanyBankAccount $account): Collection
    {
        $incomingTransfers = BankAccountTransfer::query()
            ->with('fromAccount:id,currency')
            ->where('to_account_id', $account->id)
            ->whereNotNull('exchange_rate')
            ->whereNotNull('amount_sent')
            ->whereNotNull('amount_received')
            ->get()
            ->filter(function (BankAccountTransfer $transfer): bool {
                return strtoupper((string) $transfer->fromAccount?->currency) === 'MRU';
            })
            ->map(function (BankAccountTransfer $transfer): array {
                return [
                    'type' => 'transfer_in',
                    'usd_amount' => (float) ($transfer->amount_received ?? 0),
                    'mru_amount' => (float) ($transfer->amount_sent ?? 0),
                    'date' => (string) $transfer->date?->toDateString(),
                    'id' => (int) $transfer->id,
                ];
            });

        $usdExpenses = Expense::query()
            ->where('company_account_id', $account->id)
            ->where('status', 'confirmed')
            ->where('currency', 'USD')
            ->get()
            ->map(function (Expense $expense): array {
                return [
                    'type' => 'expense_out',
                    'usd_amount' => (float) $expense->amount,
                    'mru_amount' => 0.0,
                    'date' => (string) $expense->expense_date?->toDateString(),
                    'id' => (int) $expense->id,
                ];
            });

        return $incomingTransfers
            ->concat($usdExpenses)
            ->sortBy([
                ['date', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }
}
