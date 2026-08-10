<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('kanban_columns')->where('slug', 'changes-requested')->exists()) {
            return;
        }

        DB::table('kanban_columns')
            ->where('position', '>=', 3)
            ->update(['position' => DB::raw('position + 1')]);

        DB::table('kanban_columns')->insert([
            'name'         => 'Changes Requested',
            'slug'         => 'changes-requested',
            'color'        => 'rose',
            'icon'         => '↺',
            'position'     => 3,
            'is_protected' => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('kanban_columns')->where('slug', 'changes-requested')->delete();
        DB::table('kanban_columns')
            ->where('position', '>', 3)
            ->decrement('position');
    }
};
