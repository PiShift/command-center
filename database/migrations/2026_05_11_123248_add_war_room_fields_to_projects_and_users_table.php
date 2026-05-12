<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('color', 7)->default('#4a90d9')->after('stack');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('name');
            $table->string('color', 7)->default('#D97757')->after('role');
            $table->string('initials', 3)->nullable()->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('color');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'color', 'initials']);
        });
    }
};
