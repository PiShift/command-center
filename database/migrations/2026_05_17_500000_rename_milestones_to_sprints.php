<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('milestones', 'sprints');

        Schema::table('sprints', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'completed'])
                  ->default('draft')
                  ->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::rename('sprints', 'milestones');
    }
};
