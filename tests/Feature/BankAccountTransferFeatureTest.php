<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Models\CompanyBankAccount;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BankAccountTransferFeatureTest extends TestCase
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

    public function test_same_currency_transfer_keeps_existing_amount_behavior(): void
    {
        $user = $this->createManager();
        $fromAccount = CompanyBankAccount::query()->create([
            'name' => 'MRU Source',
            'currency' => 'MRU',
        ]);
        $toAccount = CompanyBankAccount::query()->create([
            'name' => 'MRU Destination',
            'currency' => 'MRU',
        ]);

        $response = $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 1000,
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('bank-accounts.index'));
        $this->assertDatabaseHas('bank_account_transfers', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 1000,
            'amount_sent' => 1000,
            'amount_received' => 1000,
            'exchange_rate' => null,
        ]);
    }

    public function test_cross_currency_transfer_stores_sent_received_rate_updates_balances_and_renders_history_line(): void
    {
        $user = $this->createManager();
        $fromAccount = CompanyBankAccount::query()->create([
            'name' => 'MRU Source',
            'currency' => 'MRU',
        ]);
        $toAccount = CompanyBankAccount::query()->create([
            'name' => 'USD Destination',
            'currency' => 'USD',
            'usd_exchange_rate' => 40.0,
            'usd_exchange_rate_updated_at' => now()->subMinute(),
        ]);

        $fundingSource = CompanyBankAccount::query()->create([
            'name' => 'Funding Source',
            'currency' => 'MRU',
        ]);

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $fundingSource->id,
            'to_account_id' => $fromAccount->id,
            'amount' => 100000,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $response = $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount_sent' => 50000,
            'amount_received' => 1250,
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('bank-accounts.index'));
        $this->assertDatabaseHas('bank_account_transfers', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 50000,
            'amount_sent' => 50000,
            'amount_received' => 1250,
            'exchange_rate' => 40.0,
        ]);

        $this->assertSame(50000.0, round($fromAccount->fresh()->balance, 2));
        $this->assertSame(1250.0, round($toAccount->fresh()->balance, 2));

        $this->actingAs($user)->patchJson(route('bank-accounts.usd-rate.update', $toAccount), [
            'exchange_rate' => 41.75,
        ])->assertOk();

        $this->assertDatabaseHas('bank_account_transfers', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'exchange_rate' => 40.0,
        ]);

        $this->actingAs($user)
            ->get(route('bank-accounts.show', $fromAccount))
            ->assertOk()
            ->assertSee('50,000.00 MRU → $1,250.00 USD (rate: 40)', false);
    }

    public function test_usd_exchange_rate_endpoint_updates_timestamp_and_defaults_to_40_when_missing(): void
    {
        $user = $this->createManager();
        $usdAccount = CompanyBankAccount::query()->create([
            'name' => 'USD Treasury',
            'currency' => 'USD',
        ]);
        $mruSource = CompanyBankAccount::query()->create([
            'name' => 'MRU Source',
            'currency' => 'MRU',
        ]);

        $updateResponse = $this->actingAs($user)->patchJson(route('bank-accounts.usd-rate.update', $usdAccount), [
            'exchange_rate' => 40.5,
        ]);
        $updateResponse->assertOk()->assertJsonPath('exchange_rate', 40.5);

        $this->assertDatabaseHas('company_bank_accounts', [
            'id' => $usdAccount->id,
            'usd_exchange_rate' => 40.5,
        ]);

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $usdAccount->id,
            'amount_sent' => 8100,
            'amount_received' => 200,
            'date' => now()->toDateString(),
        ])->assertRedirect(route('bank-accounts.index'));

        $this->assertDatabaseHas('bank_account_transfers', [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $usdAccount->id,
            'exchange_rate' => 40.5,
        ]);

        $newUsdAccount = CompanyBankAccount::query()->create([
            'name' => 'USD Without Rate',
            'currency' => 'USD',
        ]);

        $this->actingAs($user)->post(route('bank-accounts.transfer.store'), [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $newUsdAccount->id,
            'amount_sent' => 4000,
            'amount_received' => 100,
            'date' => now()->toDateString(),
        ])->assertRedirect(route('bank-accounts.index'));

        $this->assertDatabaseHas('bank_account_transfers', [
            'from_account_id' => $mruSource->id,
            'to_account_id' => $newUsdAccount->id,
            'exchange_rate' => 40.0,
        ]);
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
