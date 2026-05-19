<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->date('month'); // always first day of month
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('MRU');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_budgets');
    }
};
