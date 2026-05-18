<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('source_type', ['overpayment', 'manual']);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('currency', 10);
            $table->decimal('amount_original', 12, 2);
            $table->decimal('amount_remaining', 12, 2);
            $table->enum('status', ['available', 'partially_used', 'fully_used'])->default('available');
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credits');
    }
};
