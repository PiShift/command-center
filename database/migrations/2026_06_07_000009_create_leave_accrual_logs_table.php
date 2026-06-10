<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_accrual_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('days_accrued', 4, 1);
            $table->timestamp('accrued_at');
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year', 'month'], 'leave_accrual_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_accrual_logs');
    }
};
