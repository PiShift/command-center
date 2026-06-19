<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove workspace_id from agent_runtimes and add team_id
        Schema::table('agent_runtimes', function (Blueprint $table) {
            if (Schema::hasColumn('agent_runtimes', 'workspace_id')) {
                // Try to drop existing constraints - ignore if they don't exist
                try {
                    $table->dropForeign(['workspace_id']);
                } catch (\Throwable $e) {
                    // Constraint doesn't exist, that's fine
                }
                
                try {
                    // Try dropping by constraint name pattern
                    DB::statement('ALTER TABLE agent_runtimes DROP CONSTRAINT IF EXISTS agent_runtimes_workspace_daemon_provider_unique');
                } catch (\Throwable $e) {
                    // If that fails, try the actual constraint check
                }
                
                $table->dropColumn('workspace_id');
            }
            
            $table->unsignedBigInteger('team_id')->nullable()->after('user_id');
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
            $table->unique(['team_id', 'daemon_id', 'provider']);
        });

        // Remove workspace_id from agent_task_queue and add team_id
        Schema::table('agent_task_queue', function (Blueprint $table) {
            if (Schema::hasColumn('agent_task_queue', 'workspace_id')) {
                try {
                    $table->dropForeign(['workspace_id']);
                } catch (\Throwable $e) {
                    // Constraint doesn't exist, that's fine
                }
                $table->dropColumn('workspace_id');
            }
            $table->unsignedBigInteger('team_id')->nullable()->after('task_id');
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });

        // Remove workspace_id from projects
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'workspace_id')) {
                try {
                    $table->dropForeign(['workspace_id']);
                } catch (\Throwable $e) {
                    // Constraint doesn't exist, that's fine
                }
                $table->dropColumn('workspace_id');
            }
        });

        // Drop workspaces table if it exists
        Schema::dropIfExists('workspaces');
    }

    public function down(): void
    {
        // Recreate workspaces table
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedBigInteger('owner_id');
            $table->enum('type', ['local', 'cloud'])->default('local');
            $table->timestamps();
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Restore workspace_id columns
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('workspace_id')->nullable()->after('customer_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
        });

        Schema::table('agent_runtimes', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
            $table->dropUnique(['team_id', 'daemon_id', 'provider']);
            $table->uuid('workspace_id')->nullable()->after('user_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->unique(['workspace_id', 'daemon_id', 'provider']);
        });

        Schema::table('agent_task_queue', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
            $table->uuid('workspace_id')->nullable()->after('task_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
        });
    }
};
