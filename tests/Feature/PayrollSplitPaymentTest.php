<?php

namespace Tests\Feature;

use App\Models\CompanyBankAccount;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\PayrollRunPayment;
use App\Models\PayrollRunPaymentEntry;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayrollSplitPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->string('color')->nullable();
                $table->string('initials')->nullable();
                $table->timestamp('onboarded_at')->nullable();
                $table->json('notification_preferences')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('company_bank_accounts')) {
            Schema::create('company_bank_accounts', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('currency', 10)->default('MRU');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_system')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table): void {
                $table->id();
                $table->date('month')->unique();
                $table->string('status')->default('draft');
                $table->decimal('total_gross', 12, 2)->default(0);
                $table->decimal('total_deductions', 12, 2)->default(0);
                $table->decimal('total_net', 12, 2)->default(0);
                $table->unsignedBigInteger('company_account_id')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_entries')) {
            Schema::create('payroll_entries', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('payroll_run_id');
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->decimal('base_salary', 12, 2);
                $table->decimal('gross_amount', 12, 2);
                $table->decimal('advances_deducted', 12, 2)->default(0);
                $table->boolean('skip_advances')->default(false);
                $table->decimal('loans_deducted', 12, 2)->default(0);
                $table->boolean('skip_loans')->default(false);
                $table->decimal('other_deductions', 12, 2)->default(0);
                $table->decimal('unpaid_leave_deduction', 12, 2)->default(0);
                $table->boolean('skip_unpaid_leave')->default(false);
                $table->decimal('bonuses', 12, 2)->default(0);
                $table->decimal('net_amount', 12, 2);
                $table->string('status')->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_profiles')) {
            Schema::create('employee_profiles', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_contracts')) {
            Schema::create('employee_contracts', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_run_payments')) {
            Schema::create('payroll_run_payments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('payroll_run_id');
                $table->unsignedBigInteger('company_account_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->timestamp('paid_at');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_run_payment_entries')) {
            Schema::create('payroll_run_payment_entries', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('payroll_run_payment_id');
                $table->unsignedBigInteger('payroll_entry_id');
                $table->decimal('amount', 12, 2);
                $table->timestamps();
            });
        }

        PayrollRunPaymentEntry::query()->delete();
        PayrollRunPayment::query()->delete();
        PayrollEntry::query()->delete();
        PayrollRun::query()->delete();
        CompanyBankAccount::query()->delete();
        User::query()->delete();
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Payroll User',
            'email' => 'payroll-'.uniqid().'@example.test',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_can_pay_selected_employees_in_multiple_batches_with_different_accounts(): void
    {
        $user = $this->createUser();

        $run = PayrollRun::query()->create([
            'month' => '2026-07-01',
            'status' => 'approved',
            'created_by' => $user->id,
        ]);

        $entryOne = PayrollEntry::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => 1,
            'base_salary' => 1000,
            'gross_amount' => 1000,
            'net_amount' => 900,
            'status' => 'pending',
        ]);

        $entryTwo = PayrollEntry::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => 2,
            'base_salary' => 1000,
            'gross_amount' => 1000,
            'net_amount' => 850,
            'status' => 'pending',
        ]);

        $entryThree = PayrollEntry::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => 3,
            'base_salary' => 1000,
            'gross_amount' => 1000,
            'net_amount' => 800,
            'status' => 'pending',
        ]);

        $accountA = CompanyBankAccount::query()->create([
            'name' => 'Account A',
            'currency' => 'MRU',
        ]);

        $accountB = CompanyBankAccount::query()->create([
            'name' => 'Account B',
            'currency' => 'MRU',
        ]);

        $this->be($user);

        app(PayrollService::class)->paySelected(
            $run,
            $accountA->id,
            [$entryOne->id, $entryTwo->id],
            'BATCH-A'
        );

        $this->assertSame('partially_paid', $run->fresh()->status);
        $this->assertNull($run->fresh()->paid_at);
        $this->assertSame('paid', $entryOne->fresh()->status);
        $this->assertSame('paid', $entryTwo->fresh()->status);
        $this->assertSame('pending', $entryThree->fresh()->status);

        $this->assertDatabaseHas('payroll_run_payments', [
            'payroll_run_id' => $run->id,
            'company_account_id' => $accountA->id,
            'reference' => 'BATCH-A',
        ]);

        app(PayrollService::class)->paySelected(
            $run,
            $accountB->id,
            [$entryThree->id],
            'BATCH-B'
        );

        $run->refresh();

        $this->assertSame('paid', $run->status);
        $this->assertNotNull($run->paid_at);
        $this->assertNull($run->company_account_id);
        $this->assertSame('paid', $entryThree->fresh()->status);
        $this->assertSame(2, PayrollRunPayment::query()->where('payroll_run_id', $run->id)->count());
        $this->assertSame(3, PayrollRunPaymentEntry::query()->count());
    }

    public function test_single_account_full_payment_keeps_run_company_account(): void
    {
        $user = $this->createUser();

        $run = PayrollRun::query()->create([
            'month' => '2026-08-01',
            'status' => 'approved',
            'created_by' => $user->id,
        ]);

        $entryOne = PayrollEntry::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => 1,
            'base_salary' => 1000,
            'gross_amount' => 1000,
            'net_amount' => 700,
            'status' => 'pending',
        ]);

        $entryTwo = PayrollEntry::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => 2,
            'base_salary' => 1000,
            'gross_amount' => 1000,
            'net_amount' => 600,
            'status' => 'pending',
        ]);

        $account = CompanyBankAccount::query()->create([
            'name' => 'Primary Account',
            'currency' => 'MRU',
        ]);

        $this->be($user);

        app(PayrollService::class)->paySelected(
            $run,
            $account->id,
            [$entryOne->id, $entryTwo->id]
        );

        $run->refresh();

        $this->assertSame('paid', $run->status);
        $this->assertSame($account->id, $run->company_account_id);

        $this->assertDatabaseHas('payroll_run_payments', [
            'payroll_run_id' => $run->id,
            'company_account_id' => $account->id,
            'amount' => 1300,
        ]);
    }
}
