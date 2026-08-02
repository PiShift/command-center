<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_run_payments')) {
            Schema::create('payroll_run_payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
                $table->foreignId('company_account_id')->nullable()->constrained('company_bank_accounts')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->timestamp('paid_at');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['payroll_run_id', 'paid_at']);
                $table->index(['company_account_id', 'paid_at']);
            });
        }

        if (! Schema::hasTable('payroll_run_payment_entries')) {
            Schema::create('payroll_run_payment_entries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payroll_run_payment_id')->constrained('payroll_run_payments')->cascadeOnDelete();
                $table->foreignId('payroll_entry_id')->constrained('payroll_entries')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->timestamps();

                $table->unique(['payroll_run_payment_id', 'payroll_entry_id'], 'payroll_run_payment_entry_unique');
                $table->index(['payroll_entry_id']);
            });
        }

        $paidRuns = DB::table('payroll_runs')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->orderBy('id')
            ->get(['id', 'company_account_id', 'total_net', 'paid_at', 'created_by']);

        foreach ($paidRuns as $run) {
            $existingBatch = DB::table('payroll_run_payments')
                ->where('payroll_run_id', $run->id)
                ->exists();

            if ($existingBatch) {
                continue;
            }

            $batchId = DB::table('payroll_run_payments')->insertGetId([
                'payroll_run_id' => $run->id,
                'company_account_id' => $run->company_account_id,
                'amount' => $run->total_net ?? 0,
                'paid_at' => $run->paid_at,
                'reference' => null,
                'notes' => 'Backfilled from legacy payroll payment data',
                'created_by' => $run->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $entries = DB::table('payroll_entries')
                ->where('payroll_run_id', $run->id)
                ->where('status', 'paid')
                ->get(['id', 'net_amount']);

            foreach ($entries as $entry) {
                DB::table('payroll_run_payment_entries')->insert([
                    'payroll_run_payment_id' => $batchId,
                    'payroll_entry_id' => $entry->id,
                    'amount' => $entry->net_amount ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_payment_entries');
        Schema::dropIfExists('payroll_run_payments');
    }
};
