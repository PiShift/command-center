<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->boolean('skip_unpaid_leave')->default(false)->after('skip_loans');
            $table->decimal('unpaid_leave_deduction', 12, 2)->default(0)->after('other_deductions');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn(['skip_unpaid_leave', 'unpaid_leave_deduction']);
        });
    }
};
