<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->decimal('exchange_rate_used', 14, 6)->nullable()->after('currency');
            $table->decimal('amount_mru', 14, 2)->nullable()->after('amount');
        });

        DB::table('expenses')
            ->whereNull('amount_mru')
            ->update([
                'amount_mru' => DB::raw('amount'),
                'exchange_rate_used' => DB::raw("CASE WHEN currency = 'MRU' THEN 1 ELSE exchange_rate_used END"),
            ]);

        Schema::table('expenses', function (Blueprint $table): void {
            $table->decimal('amount_mru', 14, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn(['exchange_rate_used', 'amount_mru']);
        });
    }
};
