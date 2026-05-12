<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_columns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();  // used as task.status value
            $table->string('color')->default('slate');
            $table->string('icon')->default('📋');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_protected')->default(false); // can't be deleted
            $table->timestamps();
        });

        // Seed the four default columns
        DB::table('kanban_columns')->insert([
            ['name' => 'Backlog',     'slug' => 'backlog',     'color' => 'slate',   'icon' => '📋', 'position' => 0, 'is_protected' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => 'blue',    'icon' => '⚡', 'position' => 1, 'is_protected' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'In Review',   'slug' => 'in-review',   'color' => 'amber',   'icon' => '👁', 'position' => 2, 'is_protected' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Done',        'slug' => 'done',        'color' => 'emerald', 'icon' => '✅', 'position' => 3, 'is_protected' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_columns');
    }
};
