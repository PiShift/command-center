<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_bank_accounts', function (Blueprint $table): void {
            $table->decimal('usd_exchange_rate', 12, 6)->nullable()->after('currency');
            $table->timestamp('usd_exchange_rate_updated_at')->nullable()->after('usd_exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('company_bank_accounts', function (Blueprint $table): void {
            $table->dropColumn(['usd_exchange_rate', 'usd_exchange_rate_updated_at']);
        });
    }
};
