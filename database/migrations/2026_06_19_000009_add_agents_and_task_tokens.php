<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->uuid('runtime_id')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->enum('visibility', ['workspace', 'private'])->default('private');
            $table->enum('status', ['idle', 'working', 'blocked', 'error', 'offline'])->default('idle');
            $table->unsignedInteger('max_concurrent_tasks')->default(6);
            $table->string('model')->nullable();
            $table->json('custom_env')->nullable();
            $table->json('custom_args')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->foreign('runtime_id')->references('id')->on('agent_runtimes')->nullOnDelete();
            $table->index(['team_id', 'owner_id']);
            $table->index(['team_id', 'visibility']);
            $table->index('archived_at');
        });

        Schema::table('agent_task_queue', function (Blueprint $table): void {
            $table->uuid('agent_id')->nullable()->after('runtime_id');
            $table->foreign('agent_id')->references('id')->on('agents')->nullOnDelete();
        });

        Schema::create('task_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash')->unique();
            $table->uuid('task_id');
            $table->uuid('agent_id');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('agent_task_queue')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_tokens');

        Schema::table('agent_task_queue', function (Blueprint $table): void {
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
        });

        Schema::dropIfExists('agents');
    }
};
