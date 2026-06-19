<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->uuid('agent_id')->nullable()->after('assigned_to');
            $table->foreign('agent_id')->references('id')->on('agents')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_agent', 'agent_cli']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_agent')->default(false)->after('initials');
            $table->string('agent_cli')->nullable()->after('is_agent');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
        });
    }
};
