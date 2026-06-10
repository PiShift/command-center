<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('company_account_id')->nullable()->constrained('company_bank_accounts')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('reason');
            $table->enum('status', ['pending', 'deducted'])->default('pending');
            $table->foreignId('payroll_entry_id')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
    }
};
