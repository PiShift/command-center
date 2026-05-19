<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Drop the MySQL enum constraint on tasks.status — status is driven
     * by kanban_columns slugs and must be a free-form string.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'backlog'");
        }
    }

    public function down(): void
    {
        // No need to restore a restrictive enum.
    }
};
