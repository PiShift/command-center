<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Shift all existing columns up by one position to make room for Open at position 0
        DB::table('kanban_columns')->increment('position');

        // Insert the protected Open column at position 0
        DB::table('kanban_columns')->insert([
            'name'         => 'Open',
            'slug'         => 'open',
            'color'        => 'violet',
            'icon'         => '🔓',
            'position'     => 0,
            'is_protected' => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('kanban_columns')->where('slug', 'open')->delete();
        DB::table('kanban_columns')->decrement('position');
    }
};
