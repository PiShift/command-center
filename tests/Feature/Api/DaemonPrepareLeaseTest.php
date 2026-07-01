<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\AgentTaskQueue;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DaemonPrepareLeaseTest extends TestCase
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

        Schema::create('agent_runtimes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('daemon_id');
            $table->string('name');
            $table->string('provider');
            $table->string('status')->default('online');
            $table->text('device_info')->nullable();
            $table->string('cli_version')->nullable();
            $table->string('launched_by')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
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

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('token_hash');
            $table->string('token_prefix')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });
    }

    public function test_prepare_lease_returns_ok_for_owned_queue(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Daemon Project', 'status' => 'active']);
        $runtime = AgentRuntime::query()->create([
            'user_id' => $user->id,
            'daemon_id' => 'daemon-1',
            'name' => 'Runtime One',
            'provider' => 'local',
            'status' => 'online',
        ]);
        $agent = Agent::query()->create([
            'owner_id' => $user->id,
            'runtime_id' => $runtime->id,
            'name' => 'Daemon Agent',
            'visibility' => 'workspace',
            'status' => 'active',
        ]);
        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Prepare lease task',
            'type' => 'feature',
            'priority' => 'medium',
            'status' => 'backlog',
        ]);
        $queue = AgentTaskQueue::query()->create([
            'task_id' => $task->id,
            'agent_id' => $agent->id,
            'runtime_id' => $runtime->id,
            'team_id' => null,
            'status' => 'queued',
            'prompt' => 'prompt',
        ]);

        $token = 'mul_'.Str::random(40);
        PersonalAccessToken::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'daemon',
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 12),
            'expires_at' => now()->addDay(),
            'revoked' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/daemon/runtimes/'.$runtime->id.'/tasks/'.$queue->id.'/prepare-lease');

        $response->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_prepare_lease_returns_not_found_for_missing_queue(): void
    {
        $user = User::factory()->create();
        $runtime = AgentRuntime::query()->create([
            'user_id' => $user->id,
            'daemon_id' => 'daemon-1',
            'name' => 'Runtime One',
            'provider' => 'local',
            'status' => 'online',
        ]);

        $token = 'mul_'.Str::random(40);
        PersonalAccessToken::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'daemon',
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 12),
            'expires_at' => now()->addDay(),
            'revoked' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/daemon/runtimes/'.$runtime->id.'/tasks/'.Str::uuid().'/prepare-lease');

        $response->assertNotFound()->assertJson(['error' => 'task not found']);
    }
}
