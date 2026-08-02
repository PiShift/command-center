<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_runtimes', function (Blueprint $table) {
            $table->dropUnique('agent_runtimes_daemon_id_provider_unique');
            $table->uuid('workspace_id')->nullable()->after('user_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->unique(['workspace_id', 'daemon_id', 'provider'], 'agent_runtimes_workspace_daemon_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::table('agent_runtimes', function (Blueprint $table) {
            $table->dropUnique('agent_runtimes_workspace_daemon_provider_unique');
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
            $table->unique(['daemon_id', 'provider']);
        });
    }
};