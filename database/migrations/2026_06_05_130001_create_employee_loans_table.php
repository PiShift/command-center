<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('company_account_id')->nullable()->constrained('company_bank_accounts')->nullOnDelete();
            $table->string('title');
            $table->decimal('amount_total', 12, 2);
            $table->enum('repayment_type', ['fixed_amount', 'percentage']);
            $table->decimal('repayment_value', 8, 2);
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
