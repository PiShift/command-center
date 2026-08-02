<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->boolean('skip_advances')->default(false)->after('advances_deducted');
            $table->boolean('skip_loans')->default(false)->after('loans_deducted');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn(['skip_advances', 'skip_loans']);
        });
    }
};