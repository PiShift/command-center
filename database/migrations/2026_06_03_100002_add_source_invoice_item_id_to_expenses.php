<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('source_invoice_item_id')
                  ->nullable()
                  ->constrained('invoice_items')
                  ->nullOnDelete()
                  ->after('recurring_charge_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\InvoiceItem::class, 'source_invoice_item_id');
            $table->dropColumn('source_invoice_item_id');
        });
    }
};
