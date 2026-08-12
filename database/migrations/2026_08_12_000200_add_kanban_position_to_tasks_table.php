<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->bigInteger('kanban_position')->nullable()->after('status');
            $table->index(['status', 'kanban_position']);
        });

        $tasks = DB::table('tasks')
            ->select('id', 'status')
            ->orderBy('status')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $nextByStatus = [];
        foreach ($tasks as $task) {
            $status = (string) ($task->status ?? 'open');
            $nextByStatus[$status] = ($nextByStatus[$status] ?? 0) + 1;

            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['kanban_position' => $nextByStatus[$status]]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_kanban_position_index');
            $table->dropColumn('kanban_position');
        });
    }
};
