<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
            $table->date('deadline')->nullable()->after('start_date');
            $table->decimal('budget', 10, 2)->nullable()->after('deadline');
            $table->enum('health', ['on-track', 'at-risk', 'blocked'])->default('on-track')->after('budget');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'deadline', 'budget', 'health']);
        });
    }
};
