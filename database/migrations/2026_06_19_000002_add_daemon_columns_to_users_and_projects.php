<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_agent')->default(false)->after('initials');
            $table->string('agent_cli')->nullable()->after('is_agent');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->json('repos')->nullable()->after('health');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('repos');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_agent', 'agent_cli']);
        });
    }
};