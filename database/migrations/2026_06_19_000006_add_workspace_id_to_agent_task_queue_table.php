<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_task_queue', function (Blueprint $table) {
            $table->uuid('workspace_id')->nullable()->after('task_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agent_task_queue', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });
    }
};