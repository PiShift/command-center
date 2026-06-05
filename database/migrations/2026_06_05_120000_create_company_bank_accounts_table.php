<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency', 10)->default('MRU');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};
