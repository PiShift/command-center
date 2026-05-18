<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move any tasks in backlog status to in-progress to avoid orphaned statuses
        DB::table('tasks')->where('status', 'backlog')->update(['status' => 'in-progress']);

        // Remove the backlog kanban column if it exists
        DB::table('kanban_columns')->where('slug', 'backlog')->delete();
    }

    public function down(): void
    {
        // No rollback — we cannot recover deleted data reliably
    }
};
