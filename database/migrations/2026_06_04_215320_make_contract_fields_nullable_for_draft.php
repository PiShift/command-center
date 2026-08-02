<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            // Allow null so draft contracts can be saved with incomplete data
            $table->decimal('base_salary', 12, 2)->nullable()->change();
            $table->date('effective_from')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->decimal('base_salary', 12, 2)->nullable(false)->change();
            $table->date('effective_from')->nullable(false)->change();
        });
    }
};
