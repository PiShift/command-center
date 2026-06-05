<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('company_account_id')
                ->nullable()
                ->constrained('company_bank_accounts')
                ->nullOnDelete()
                ->after('customer_id');

            $table->string('method')->nullable()->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('company_account_id')
                ->nullable()
                ->constrained('company_bank_accounts')
                ->nullOnDelete()
                ->after('project_id');
        });

        DB::table('invoice_payments')->update(['method' => null]);
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_account_id');
            $table->string('method')->nullable(false)->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_account_id');
        });
    }
};
