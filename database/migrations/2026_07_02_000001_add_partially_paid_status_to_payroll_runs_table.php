<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_runs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payroll_runs MODIFY status ENUM('draft','approved','partially_paid','paid') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_runs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('payroll_runs')
                ->where('status', 'partially_paid')
                ->update(['status' => 'approved']);

            DB::statement("ALTER TABLE payroll_runs MODIFY status ENUM('draft','approved','paid') NOT NULL DEFAULT 'draft'");
        }
    }
};
