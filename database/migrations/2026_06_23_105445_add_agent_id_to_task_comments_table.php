<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->uuid('agent_id')->nullable()->after('user_id');
            $table->foreign('agent_id')->references('id')->on('agents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['agent_id']);
            $table->dropColumn('agent_id');
        });
    }
};
