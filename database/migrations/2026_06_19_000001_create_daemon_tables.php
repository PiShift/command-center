<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daemon_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash')->unique();
            $table->string('name');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_runtimes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('daemon_id');
            $table->string('name');
            $table->string('provider');
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->string('device_info')->nullable();
            $table->string('cli_version')->nullable();
            $table->string('launched_by')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['daemon_id', 'provider']);
        });

        Schema::create('agent_task_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->uuid('runtime_id')->nullable();
            $table->enum('status', ['queued', 'dispatched', 'running', 'waiting', 'completed', 'failed', 'cancelled'])->default('queued');
            $table->longText('prompt')->nullable();
            $table->longText('output')->nullable();
            $table->text('error_message')->nullable();
            $table->string('pr_url')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('runtime_id')->references('id')->on('agent_runtimes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_task_queue');
        Schema::dropIfExists('agent_runtimes');
        Schema::dropIfExists('daemon_tokens');
    }
};