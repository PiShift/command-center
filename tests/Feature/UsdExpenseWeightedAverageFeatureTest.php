<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Models\CompanyBankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\ExpenseService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsdExpenseWeightedAverageFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RequiresTwoFactor::class);
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }

    public function test_usd_weighted_average_updates_across_transfer_spend_and_next_transfer(): void
    {
        $user = $this->createManager();
        $category = ExpenseCategory::query()->create(['name' => 'Ops']);
        $mruSource = CompanyBankAccount::query()->create(['name' => 'MRU Source', 'currency' => 'MRU']);
        $usdAccount = CompanyBankAccount::query()->create(['name' => 'USD Wallet', 'currency' => 'USD']);
        $funding = CompanyBankAccount::query()->create(['name' => 'Funding', 'currency' => 'MRU']);

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $funding->id,
            'to_account_id' => $mruSource->id,
            'amount' => 100000,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $usdAccount->id,
            'amount_sent' => 4000,
            'amount_received' => 100,
            'exchange_rate' => 40,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $usdAccount->refresh();
        $this->assertSame(4000.0, round((float) $usdAccount->usd_cost_basis_mru, 2));
        $this->assertSame(40.0, round((float) $usdAccount->usd_weighted_average_rate, 2));

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => 'USD Spend 1',
            'category_id' => $category->id,
            'company_account_id' => $usdAccount->id,
            'amount' => 80,
            'expense_date' => now()->toDateString(),
            'status' => 'confirmed',
        ])->assertRedirect(route('expenses.monthly-overview'));

        $firstUsdExpense = Expense::query()->where('title', 'USD Spend 1')->firstOrFail();
        $this->assertSame('USD', $firstUsdExpense->currency);
        $this->assertSame(40.0, round((float) $firstUsdExpense->exchange_rate_used, 2));
        $this->assertSame(3200.0, round((float) $firstUsdExpense->amount_mru, 2));

        $usdAccount->refresh();
        $this->assertSame(800.0, round((float) $usdAccount->usd_cost_basis_mru, 2));
        $this->assertSame(40.0, round((float) $usdAccount->usd_weighted_average_rate, 2));

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $usdAccount->id,
            'amount_sent' => 2050,
            'amount_received' => 50,
            'exchange_rate' => 41,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $usdAccount->refresh();
        $this->assertSame(2850.0, round((float) $usdAccount->usd_cost_basis_mru, 2));
        $this->assertSame(40.714286, round((float) $usdAccount->usd_weighted_average_rate, 6));

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => 'USD Spend 2',
            'category_id' => $category->id,
            'company_account_id' => $usdAccount->id,
            'amount' => 10,
            'expense_date' => now()->toDateString(),
            'status' => 'confirmed',
        ])->assertRedirect(route('expenses.monthly-overview'));

        $secondUsdExpense = Expense::query()->where('title', 'USD Spend 2')->firstOrFail();
        $this->assertSame(40.714286, round((float) $secondUsdExpense->exchange_rate_used, 6));
        $this->assertSame(407.14, round((float) $secondUsdExpense->amount_mru, 2));

        $usdAccount->refresh();
        $this->assertSame(40.714286, round((float) $usdAccount->usd_weighted_average_rate, 6));
    }

    public function test_usd_cost_basis_resets_when_usd_balance_reaches_zero(): void
    {
        $user = $this->createManager();
        $category = ExpenseCategory::query()->create(['name' => 'Ops']);
        $mruSource = CompanyBankAccount::query()->create(['name' => 'MRU Source', 'currency' => 'MRU']);
        $usdAccount = CompanyBankAccount::query()->create(['name' => 'USD Wallet', 'currency' => 'USD']);
        $funding = CompanyBankAccount::query()->create(['name' => 'Funding', 'currency' => 'MRU']);

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $funding->id,
            'to_account_id' => $mruSource->id,
            'amount' => 100000,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $usdAccount->id,
            'amount_sent' => 4000,
            'amount_received' => 100,
            'exchange_rate' => 40,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => 'USD Full Spend',
            'category_id' => $category->id,
            'company_account_id' => $usdAccount->id,
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'status' => 'confirmed',
        ])->assertRedirect(route('expenses.monthly-overview'));

        $usdAccount->refresh();
        $this->assertSame(0.0, round((float) $usdAccount->usd_cost_basis_mru, 2));
        $this->assertSame(0.0, round((float) $usdAccount->usd_weighted_average_rate, 2));

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $usdAccount->id,
            'amount_sent' => 2050,
            'amount_received' => 50,
            'exchange_rate' => 41,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $usdAccount->refresh();
        $this->assertSame(2050.0, round((float) $usdAccount->usd_cost_basis_mru, 2));
        $this->assertSame(41.0, round((float) $usdAccount->usd_weighted_average_rate, 2));
    }

    public function test_expense_views_and_summary_use_mru_equivalent_with_original_non_mru_parentheses(): void
    {
        $user = $this->createManager();
        $category = ExpenseCategory::query()->create(['name' => 'Operations']);
        $mruAccount = CompanyBankAccount::query()->create(['name' => 'MRU Cash', 'currency' => 'MRU']);
        $usdAccount = CompanyBankAccount::query()->create([
            'name' => 'USD Wallet',
            'currency' => 'USD',
            'usd_weighted_average_rate' => 40,
        ]);

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => 'MRU Expense',
            'category_id' => $category->id,
            'company_account_id' => $mruAccount->id,
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
            'status' => 'confirmed',
        ])->assertRedirect(route('expenses.monthly-overview'));

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => 'USD Expense',
            'category_id' => $category->id,
            'company_account_id' => $usdAccount->id,
            'amount' => 10,
            'expense_date' => now()->toDateString(),
            'status' => 'confirmed',
        ])->assertRedirect(route('expenses.monthly-overview'));

        $this->actingAs($user)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('1,400.00')
            ->assertSee('($10.00)');

        $summary = app(ExpenseService::class)->getMonthlySummary(now()->startOfMonth());
        $this->assertSame(1400.0, round((float) $summary['totals']['actual_amount'], 2));
    }

    private function createManager(): User
    {
        $role = Role::query()->where('slug', 'manager')->firstOrFail();

        return User::query()->create([
            'name' => 'Finance Manager',
            'email' => 'manager-'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);
    }
}
