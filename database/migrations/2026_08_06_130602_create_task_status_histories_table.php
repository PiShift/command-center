<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 50);
            $table->string('to_status', 50);
            $table->string('actor_type', 32)->default('system');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('actor_agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('actor_label')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_histories');
    }
};
