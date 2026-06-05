<?php

namespace Database\Seeders;

use App\Models\CompanyBankAccount;
use App\Models\Expense;
use App\Models\InvoicePayment;
use Illuminate\Database\Seeder;

class CompanyBankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $cash = CompanyBankAccount::updateOrCreate(
            ['name' => 'Cash'],
            [
                'bank_name' => null,
                'account_number' => null,
                'currency' => 'MRU',
                'is_default' => true,
                'notes' => null,
            ]
        );
        $cash->forceFill(['is_system' => true])->save();

        $bnm = CompanyBankAccount::updateOrCreate(
            ['name' => 'Compte Principal BNM'],
            [
                'bank_name' => 'BNM',
                'account_number' => null,
                'currency' => 'MRU',
                'is_default' => false,
                'notes' => null,
            ]
        );
        $bnm->forceFill(['is_system' => true])->save();

        $cashId = CompanyBankAccount::where('is_system', true)
            ->where('name', 'Cash')
            ->value('id');

        if (! $cashId) {
            return;
        }

        InvoicePayment::whereNull('company_account_id')
            ->update(['company_account_id' => $cashId]);

        Expense::whereNull('company_account_id')
            ->update(['company_account_id' => $cashId]);
    }
}
