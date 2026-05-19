<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_charges', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('next_due_date');
            $table->unsignedSmallInteger('max_occurrences')->nullable()->after('end_date');
            $table->unsignedSmallInteger('occurrences_count')->default(0)->after('max_occurrences');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_charges', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'max_occurrences', 'occurrences_count']);
        });
    }
};
