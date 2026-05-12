<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('project_id')->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->after('status');
            $table->unsignedSmallInteger('estimated_hours')->nullable()->after('due_date');
            $table->json('labels')->nullable()->after('estimated_hours');
            $table->timestamp('completed_at')->nullable()->after('labels');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['due_date', 'estimated_hours', 'labels', 'completed_at']);
        });
    }
};
