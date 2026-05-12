<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: drop and recreate the check constraint to add 'in-review'
        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check');
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status = ANY (ARRAY['backlog', 'in-progress', 'in-review', 'done']))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check');
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status = ANY (ARRAY['backlog', 'in-progress', 'done']))");
    }
};
