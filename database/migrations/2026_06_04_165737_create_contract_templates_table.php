<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('employment_type', ['CDI', 'CDD', 'freelance', 'internship', 'all']);
            $table->longText('content');
            $table->enum('language', ['fr', 'ar', 'en'])->default('fr');
            $table->string('version', 20)->default('v1.0');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};
