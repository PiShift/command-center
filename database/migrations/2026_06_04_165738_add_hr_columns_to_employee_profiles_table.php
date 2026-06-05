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
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('nni')->nullable()->after('notes');
            $table->string('nationality')->nullable()->default('Mauritanienne')->after('nni');
            $table->date('date_of_birth')->nullable()->after('nationality');
            $table->string('work_location')->nullable()->default('Nouakchott')->after('date_of_birth');
            $table->string('category')->nullable()->after('work_location');
            $table->tinyInteger('probation_period_months')->unsigned()->default(2)->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['nni', 'nationality', 'date_of_birth', 'work_location', 'category', 'probation_period_months']);
        });
    }
};
