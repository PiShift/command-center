<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_task_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('task_queue_id');
            $table->integer('seq');
            $table->string('type');
            $table->string('tool')->nullable();
            $table->longText('content')->nullable();
            $table->json('input')->nullable();
            $table->longText('output')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('task_queue_id')->references('id')->on('agent_task_queue')->cascadeOnDelete();
            $table->index(['task_queue_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_task_messages');
    }
};
