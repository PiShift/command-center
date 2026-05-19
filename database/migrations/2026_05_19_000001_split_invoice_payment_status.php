<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the new payment_status column (idempotent — safe to re-run)
        if (! Schema::hasColumn('invoices', 'payment_status')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])
                      ->default('unpaid')
                      ->after('amount_paid');
            });
        }

        // 2. Derive payment_status from old combined status
        DB::statement("
            UPDATE invoices
            SET payment_status = CASE
                WHEN status = 'paid'           THEN 'paid'
                WHEN status = 'partially_paid' THEN 'partially_paid'
                ELSE 'unpaid'
            END
        ");

        // 3. Collapse payment values in status → 'published'
        DB::statement("
            UPDATE invoices
            SET status = 'published'
            WHERE status IN ('paid', 'partially_paid')
        ");

        // 4. Narrow status to lifecycle values only (driver-specific)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('draft', 'published', 'cancelled'))");
        } else {
            // MySQL: ENUM is the column type itself
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        // 1. Widen status first so the data restore doesn't violate the constraint
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('draft', 'published', 'partially_paid', 'paid', 'cancelled'))");
        } else {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','published','partially_paid','paid','cancelled') NOT NULL DEFAULT 'draft'");
        }

        // 2. Restore combined status values from the two columns
        DB::statement("
            UPDATE invoices
            SET status = CASE
                WHEN payment_status = 'paid'           THEN 'paid'
                WHEN payment_status = 'partially_paid' THEN 'partially_paid'
                ELSE status
            END
        ");

        // 3. Drop the payment_status column
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
