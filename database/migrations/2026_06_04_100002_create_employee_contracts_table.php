<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->enum('employment_type', ['CDI', 'CDD', 'freelance', 'internship', 'part_time']);
            $table->decimal('base_salary', 12, 2);
            $table->string('currency', 10)->default('MRU');
            $table->decimal('working_hours_per_day', 4, 1)->default(8.0);
            $table->tinyInteger('working_days_per_week')->default(5);
            $table->integer('notice_period_days')->default(30);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('additional_clauses')->nullable();
            $table->enum('status', ['draft', 'active', 'terminated'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};
