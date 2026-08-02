<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE agent_task_queue MODIFY COLUMN status ENUM('queued', 'dispatched', 'running', 'waiting', 'waiting_local_directory', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'queued'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE agent_task_queue MODIFY COLUMN status ENUM('queued', 'dispatched', 'running', 'waiting', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'queued'");
    }
};
