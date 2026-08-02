<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_bank_accounts', function (Blueprint $table): void {
            $table->decimal('usd_cost_basis_mru', 14, 6)->default(0)->after('usd_exchange_rate_updated_at');
            $table->decimal('usd_weighted_average_rate', 14, 6)->default(0)->after('usd_cost_basis_mru');
        });
    }

    public function down(): void
    {
        Schema::table('company_bank_accounts', function (Blueprint $table): void {
            $table->dropColumn(['usd_cost_basis_mru', 'usd_weighted_average_rate']);
        });
    }
};
