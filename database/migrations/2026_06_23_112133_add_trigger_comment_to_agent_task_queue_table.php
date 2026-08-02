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
        Schema::table('agent_task_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('trigger_comment_id')->nullable()->after('prompt');
            $table->text('trigger_comment_content')->nullable()->after('trigger_comment_id');
            $table->foreign('trigger_comment_id')->references('id')->on('task_comments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_task_queue', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['trigger_comment_id']);
            $table->dropColumn('trigger_comment_id', 'trigger_comment_content');
        });
    }
};
