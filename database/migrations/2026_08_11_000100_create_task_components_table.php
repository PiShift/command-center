<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_components', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $components = DB::table('tasks')
            ->selectRaw('TRIM(component) as component')
            ->whereNotNull('component')
            ->whereRaw("TRIM(component) <> ''")
            ->distinct()
            ->orderBy('component')
            ->pluck('component')
            ->values();

        if ($components->isEmpty()) {
            $components = collect(['Other']);
        }

        $now = now();
        foreach ($components as $index => $component) {
            DB::table('task_components')->insert([
                'name' => $component,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_components');
    }
};
