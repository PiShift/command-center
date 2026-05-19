<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('kanban_columns')->where('slug', 'todo')->exists()) {
            return;
        }

        DB::table('kanban_columns')->insert([
            'name'         => 'Todo',
            'slug'         => 'todo',
            'color'        => 'violet',
            'icon'         => '📝',
            'position'     => 1,
            'is_protected' => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('kanban_columns')->where('slug', 'todo')->delete();
    }
};
