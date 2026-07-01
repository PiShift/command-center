<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\AgentTaskQueue;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IssueActionCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->string('color')->nullable();
            $table->string('initials')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('lead_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_team', function (Blueprint $table): void {
            $table->foreignId('project_id');
            $table->foreignId('team_id');
            $table->primary(['project_id', 'team_id']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('github_repo')->nullable();
            $table->string('stack')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('sprint_id')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->uuid('agent_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('feature');
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->date('due_date')->nullable();
            $table->integer('estimated_hours')->nullable();
            $table->integer('weight')->nullable();
            $table->json('labels')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('source')->default('manual');
            $table->text('original_input')->nullable();
            $table->text('guide')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('task_checklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->string('label');
            $table->boolean('is_checked')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('agents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->uuid('runtime_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('visibility')->default('workspace');
            $table->string('status')->default('active');
            $table->unsignedInteger('max_concurrent_tasks')->default(1);
            $table->string('model')->nullable();
            $table->json('custom_env')->nullable();
            $table->json('custom_args')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_task_queue', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->uuid('runtime_id')->nullable();
            $table->uuid('agent_id')->nullable();
            $table->string('status')->default('queued');
            $table->longText('prompt')->nullable();
            $table->longText('output')->nullable();
            $table->text('error_message')->nullable();
            $table->string('pr_url')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('trigger_comment_id')->nullable();
            $table->text('trigger_comment_content')->nullable();
            $table->timestamps();
        });
    }

    public function test_create_with_agent_sets_owner_as_assigned_to_and_queues_task(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        $project = Project::query()->create([
            'name' => 'Queue Project',
            'status' => 'active',
        ]);

        $agent = Agent::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Autopilot Agent',
            'runtime_id' => '11111111-1111-1111-1111-111111111111',
            'team_id' => null,
            'visibility' => 'workspace',
            'status' => 'active',
        ]);

        $response = $this->actingAs($viewer)->postJson('/api/issues', [
            'title' => 'Queue me',
            'project_id' => $project->id,
            'assignee_type' => 'agent',
            'assignee_id' => 'agent-'.$agent->id,
        ]);

        $response->assertCreated();

        $taskId = (int) $response->json('id');
        $task = Task::query()->findOrFail($taskId);

        $this->assertSame($agent->id, $task->agent_id);
        $this->assertSame($owner->id, $task->assigned_to);

        $queue = AgentTaskQueue::query()->where('task_id', $task->id)->first();
        $this->assertNotNull($queue);
        $this->assertSame($agent->id, $queue->agent_id);
        $this->assertSame('queued', $queue->status);
    }
}
