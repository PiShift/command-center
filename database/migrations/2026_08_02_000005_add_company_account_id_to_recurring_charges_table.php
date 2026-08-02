<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_charges', function (Blueprint $table) {
            $table->foreignId('company_account_id')
                ->nullable()
                ->after('project_id')
                ->constrained('company_bank_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recurring_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_account_id');
        });
    }
};
