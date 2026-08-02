<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Models\CompanyBankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RecurringCharge;
use App\Models\Role;
use App\Models\User;
use App\Services\ExpenseService;
use Carbon\Carbon;
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
            ->assertSee('($10.00)')
            ->assertSee('expenses-header-actions');

        $expense = Expense::query()->where('title', 'USD Expense')->firstOrFail();

        $this->actingAs($user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('type="checkbox" name="is_recurring"', false)
            ->assertSee('recurring: false')
            ->assertSee('expense-form-two-col')
            ->assertSee('x-show.important="recurring"', false);

        $this->actingAs($user)
            ->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('type="checkbox" name="is_recurring"', false)
            ->assertSee('expense-form-recurring-grid')
            ->assertSee('x-show.important="recurring"', false);

        $this->actingAs($user)
            ->get(route('expenses.monthly-overview'))
            ->assertOk()
            ->assertSee('expenses-overview-tabs');

        $summary = app(ExpenseService::class)->getMonthlySummary(now()->startOfMonth());
        $this->assertSame(1400.0, round((float) $summary['totals']['actual_amount'], 2));
    }

    public function test_recurring_drafts_keep_usd_currency_and_use_account_weighted_rate(): void
    {
        $month = Carbon::create(2026, 8, 1);
        $category = ExpenseCategory::query()->create(['name' => 'Operations']);
        $usdAccount = CompanyBankAccount::query()->create([
            'name' => 'USD Wallet',
            'currency' => 'USD',
            'usd_weighted_average_rate' => 40.5,
        ]);

        $charge = RecurringCharge::query()->create([
            'name' => 'Cloud hosting',
            'category_id' => $category->id,
            'company_account_id' => $usdAccount->id,
            'amount' => 19.00,
            'currency' => 'USD',
            'frequency' => 'monthly',
            'start_date' => $month->toDateString(),
            'next_due_date' => $month->toDateString(),
            'is_active' => true,
        ]);

        $created = app(ExpenseService::class)->generateRecurringDrafts($month);

        $this->assertSame(1, $created);

        $expense = Expense::query()->where('recurring_charge_id', $charge->id)->firstOrFail();
        $this->assertSame($usdAccount->id, $expense->company_account_id);
        $this->assertSame('USD', $expense->currency);
        $this->assertSame(40.5, round((float) $expense->exchange_rate_used, 2));
        $this->assertSame(769.5, round((float) $expense->amount_mru, 2));
    }

    public function test_edit_form_defaults_recurring_toggle_to_off_for_inactive_recurring_charge(): void
    {
        $user = $this->createManager();
        $category = ExpenseCategory::query()->create(['name' => 'Operations']);
        $companyAccount = CompanyBankAccount::query()->create(['name' => 'Cash', 'currency' => 'MRU']);

        $recurringCharge = RecurringCharge::query()->create([
            'name' => 'Domain renewal',
            'category_id' => $category->id,
            'company_account_id' => $companyAccount->id,
            'amount' => 120.00,
            'currency' => 'MRU',
            'frequency' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'next_due_date' => now()->startOfMonth()->toDateString(),
            'is_active' => false,
        ]);

        $expense = Expense::query()->create([
            'title' => 'Domain renewal',
            'category_id' => $category->id,
            'company_account_id' => $companyAccount->id,
            'recurring_charge_id' => $recurringCharge->id,
            'amount' => 120.00,
            'expense_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('recurring: false');
    }

    public function test_updating_expense_does_not_reactivate_inactive_recurring_charge(): void
    {
        $user = $this->createManager();
        $category = ExpenseCategory::query()->create(['name' => 'Operations']);
        $companyAccount = CompanyBankAccount::query()->create(['name' => 'Cash', 'currency' => 'MRU']);

        $recurringCharge = RecurringCharge::query()->create([
            'name' => 'Domain renewal',
            'category_id' => $category->id,
            'company_account_id' => $companyAccount->id,
            'amount' => 120.00,
            'currency' => 'MRU',
            'frequency' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'next_due_date' => now()->startOfMonth()->toDateString(),
            'is_active' => false,
        ]);

        $expense = Expense::query()->create([
            'title' => 'Domain renewal',
            'category_id' => $category->id,
            'company_account_id' => $companyAccount->id,
            'recurring_charge_id' => $recurringCharge->id,
            'amount' => 120.00,
            'expense_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($user)->put(route('expenses.update', $expense), [
            'title' => 'Domain renewal updated',
            'category_id' => $category->id,
            'project_id' => null,
            'company_account_id' => $companyAccount->id,
            'amount' => 130.00,
            'expense_date' => now()->toDateString(),
            'status' => 'draft',
            'is_recurring' => '1',
            'rec_frequency' => 'monthly',
            'rec_start_date' => now()->startOfMonth()->toDateString(),
            'rec_end_date' => null,
            'rec_max_occurrences' => null,
        ])->assertRedirect(route('expenses.monthly-overview'));

        $this->assertFalse($recurringCharge->fresh()->is_active);
    }

    public function test_toggled_recurring_charge_is_skipped_when_disabled_and_used_when_reenabled(): void
    {
        $user = $this->createManager();
        $month = Carbon::create(2026, 8, 1);
        $category = ExpenseCategory::query()->create(['name' => 'Operations']);
        $companyAccount = CompanyBankAccount::query()->create(['name' => 'Cash', 'currency' => 'MRU']);

        $charge = RecurringCharge::query()->create([
            'name' => 'Cloud hosting',
            'category_id' => $category->id,
            'company_account_id' => $companyAccount->id,
            'amount' => 19.00,
            'currency' => 'MRU',
            'frequency' => 'monthly',
            'start_date' => $month->toDateString(),
            'next_due_date' => $month->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('recurring-charges.toggle', $charge))
            ->assertRedirect();
        $this->assertFalse($charge->fresh()->is_active);

        $this->assertSame(0, app(ExpenseService::class)->generateRecurringDrafts($month));
        $this->assertDatabaseMissing('expenses', ['recurring_charge_id' => $charge->id]);

        $this->actingAs($user)
            ->post(route('recurring-charges.toggle', $charge))
            ->assertRedirect();
        $this->assertTrue($charge->fresh()->is_active);

        $this->assertSame(1, app(ExpenseService::class)->generateRecurringDrafts($month));
        $this->assertDatabaseHas('expenses', ['recurring_charge_id' => $charge->id]);
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
