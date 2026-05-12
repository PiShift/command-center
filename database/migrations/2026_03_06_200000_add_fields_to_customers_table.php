<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->enum('status', ['prospect', 'active', 'churned'])->default('prospect')->after('website');
            $table->string('industry')->nullable()->after('status');
            $table->string('avatar_url')->nullable()->after('industry');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'website', 'status', 'industry', 'avatar_url']);
        });
    }
};
