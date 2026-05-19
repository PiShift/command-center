<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the new payment_status column
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])
                  ->default('unpaid')
                  ->after('amount_paid');
        });

        // 2. Migrate existing data: derive payment_status from the old combined status
        DB::statement("
            UPDATE invoices
            SET payment_status = CASE
                WHEN status = 'paid'           THEN 'paid'
                WHEN status = 'partially_paid' THEN 'partially_paid'
                ELSE 'unpaid'
            END
        ");

        // 3. Normalise status — 'paid' and 'partially_paid' become 'published'
        DB::statement("
            UPDATE invoices
            SET status = 'published'
            WHERE status IN ('paid', 'partially_paid')
        ");

        // 4. Tighten the status check constraint to lifecycle values only.
        //    Laravel's enum() creates a VARCHAR + check constraint in PostgreSQL.
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check
            CHECK (status IN ('draft', 'published', 'cancelled'))
        ");
    }

    public function down(): void
    {
        // Restore combined status from the two columns
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');

        DB::statement("
            UPDATE invoices
            SET status = CASE
                WHEN payment_status = 'paid'           THEN 'paid'
                WHEN payment_status = 'partially_paid' THEN 'partially_paid'
                ELSE status
            END
        ");

        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check
            CHECK (status IN ('draft', 'published', 'partially_paid', 'paid', 'cancelled'))
        ");

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
