<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Task status is now driven by the kanban_columns table (arbitrary slugs),
     * so the static check constraint must be dropped.
     */
    public function up(): void
    {
        // PostgreSQL: drop the enum-style check constraint that was added earlier
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check');
        }
        // MySQL: no check constraint was ever added, nothing to drop.
    }

    public function down(): void
    {
        // Restore a minimal constraint (current known values only)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE tasks
                ADD CONSTRAINT tasks_status_check
                CHECK (status IN ('backlog','in-progress','in-review','done'))
            ");
        }
    }
};
