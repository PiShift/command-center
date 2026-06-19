<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_task_usage', function (Blueprint $table): void {
            $table->id();
            $table->uuid('task_queue_id');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->string('model')->nullable();
            $table->timestamp('created_at');

            $table->foreign('task_queue_id')->references('id')->on('agent_task_queue')->cascadeOnDelete();
            $table->index('task_queue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_task_usage');
    }
};