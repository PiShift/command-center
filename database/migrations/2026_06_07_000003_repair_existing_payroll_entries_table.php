<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payroll_entries')) {
            return;
        }

        Schema::table('payroll_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_entries', 'payroll_run_id')) {
                $table->unsignedBigInteger('payroll_run_id');
            }

            if (!Schema::hasColumn('payroll_entries', 'employee_id')) {
                $table->unsignedBigInteger('employee_id');
            }

            if (!Schema::hasColumn('payroll_entries', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable();
            }

            if (!Schema::hasColumn('payroll_entries', 'base_salary')) {
                $table->decimal('base_salary', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('payroll_entries', 'gross_amount')) {
                $table->decimal('gross_amount', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('payroll_entries', 'advances_deducted')) {
                $table->decimal('advances_deducted', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('payroll_entries', 'loans_deducted')) {
                $table->decimal('loans_deducted', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('payroll_entries', 'other_deductions')) {
                $table->decimal('other_deductions', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('payroll_entries', 'bonuses')) {
                $table->decimal('bonuses', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('payroll_entries', 'net_amount')) {
                $table->decimal('net_amount', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('payroll_entries', 'status')) {
                $table->string('status')->default('pending');
            }

            if (!Schema::hasColumn('payroll_entries', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }

            if (!Schema::hasColumn('payroll_entries', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        $this->addConstraintIfMissing(
            'payroll_entries_payroll_run_id_foreign',
            'ALTER TABLE payroll_entries ADD CONSTRAINT payroll_entries_payroll_run_id_foreign FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE'
        );

        $this->addConstraintIfMissing(
            'payroll_entries_employee_id_foreign',
            'ALTER TABLE payroll_entries ADD CONSTRAINT payroll_entries_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES employee_profiles(id) ON DELETE CASCADE'
        );

        $this->addConstraintIfMissing(
            'payroll_entries_contract_id_foreign',
            'ALTER TABLE payroll_entries ADD CONSTRAINT payroll_entries_contract_id_foreign FOREIGN KEY (contract_id) REFERENCES employee_contracts(id) ON DELETE SET NULL'
        );

        $this->addConstraintIfMissing(
            'payroll_entries_status_check',
            "ALTER TABLE payroll_entries ADD CONSTRAINT payroll_entries_status_check CHECK (status IN ('pending', 'paid'))"
        );

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS payroll_entries_payroll_run_id_employee_id_unique ON payroll_entries (payroll_run_id, employee_id)'
        );

        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN payroll_run_id SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN employee_id SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN base_salary SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN gross_amount SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN advances_deducted SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN loans_deducted SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN other_deductions SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN bonuses SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN net_amount SET NOT NULL');
        DB::statement('ALTER TABLE payroll_entries ALTER COLUMN status SET NOT NULL');
    }

    public function down(): void
    {
        // Intentionally no-op: this migration repairs legacy table shape in-place.
    }

    private function addConstraintIfMissing(string $constraintName, string $statement): void
    {
        $exists = DB::table('pg_constraint')
            ->where('conname', $constraintName)
            ->exists();

        if (!$exists) {
            DB::statement($statement);
        }
    }
};
