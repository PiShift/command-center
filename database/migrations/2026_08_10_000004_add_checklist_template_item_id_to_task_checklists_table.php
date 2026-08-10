<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_checklists', function (Blueprint $table) {
            $table->foreignId('checklist_template_item_id')
                ->nullable()
                ->after('task_id')
                ->constrained('checklist_template_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_checklists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checklist_template_item_id');
        });
    }
};
