<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->foreignId('payroll_entry_id')->nullable()->constrained('payroll_entries')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('salary_snapshot', 12, 2);
            $table->decimal('percentage_snapshot', 5, 2)->nullable();
            $table->date('repayment_date');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_repayments');
    }
};
