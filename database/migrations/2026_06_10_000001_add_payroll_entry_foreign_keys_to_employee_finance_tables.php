<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_entries')) {
            return;
        }

        $this->addForeignKeyIfMissing(
            'employee_advances',
            'employee_advances_payroll_entry_id_foreign'
        );

        $this->addForeignKeyIfMissing(
            'employee_loan_repayments',
            'employee_loan_repayments_payroll_entry_id_foreign'
        );
    }

    public function down(): void
    {
        // Intentionally left blank: dropping these constraints is not required for rollback in this app.
    }

    private function addForeignKeyIfMissing(string $table, string $constraintName): void
    {
        if ($this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreign('payroll_entry_id')
                ->references('id')
                ->on('payroll_entries')
                ->nullOnDelete();
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'select exists(select 1 from pg_constraint where conname = ?) as exists',
                [$constraintName]
            );

            return (bool) ($row->exists ?? false);
        }

        $row = DB::selectOne(
            'select exists(select 1 from information_schema.table_constraints where constraint_schema = database() and table_name = ? and constraint_name = ?) as exists',
            [$table, $constraintName]
        );

        return (bool) ($row->exists ?? false);
    }
};