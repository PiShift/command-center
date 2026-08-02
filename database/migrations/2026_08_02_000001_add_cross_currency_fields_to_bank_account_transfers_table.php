<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_account_transfers', function (Blueprint $table): void {
            $table->decimal('amount_sent', 12, 2)->nullable()->after('amount');
            $table->decimal('amount_received', 12, 2)->nullable()->after('amount_sent');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('amount_received');
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transfers', function (Blueprint $table): void {
            $table->dropColumn(['amount_sent', 'amount_received', 'exchange_rate']);
        });
    }
};
