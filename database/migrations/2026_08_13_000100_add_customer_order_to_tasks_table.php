<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_order')->nullable()->after('kanban_position');
            $table->index(['project_id', 'customer_order']);
        });

        $tasks = DB::table('tasks')
            ->select(['id', 'project_id'])
            ->orderBy('project_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $nextByProject = [];

        foreach ($tasks as $task) {
            $projectId = (int) ($task->project_id ?? 0);

            if ($projectId <= 0) {
                continue;
            }

            $nextByProject[$projectId] = ($nextByProject[$projectId] ?? 0) + 1;

            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['customer_order' => $nextByProject[$projectId]]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_project_id_customer_order_index');
            $table->dropColumn('customer_order');
        });
    }
};
