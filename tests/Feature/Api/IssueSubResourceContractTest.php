<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IssueSubResourceContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

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

        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
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

        Schema::create('project_team', function (Blueprint $table): void {
            $table->foreignId('project_id');
            $table->foreignId('team_id');
            $table->primary(['project_id', 'team_id']);
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
    }

    public function test_subscribers_returns_empty_array_when_no_assignee(): void
    {
        ['viewer' => $viewer, 'task' => $task] = $this->makeFixture(withAssignee: false);

        $response = $this->actingAs($viewer)->getJson('/api/issues/' . $task->id . '/subscribers');

        $response->assertOk()->assertExactJson([]);
    }

    public function test_subscribers_returns_correct_contract_when_assignee_present(): void
    {
        ['viewer' => $viewer, 'task' => $task, 'assignee' => $assignee] = $this->makeFixture(withAssignee: true);

        $response = $this->actingAs($viewer)->getJson('/api/issues/' . $task->id . '/subscribers');

        $response->assertOk();

        $items = $response->json();
        $this->assertCount(1, $items);

        $item = $items[0];

        // Exact keys required by Multica schema
        $this->assertArrayHasKey('issue_id', $item);
        $this->assertArrayHasKey('user_type', $item);
        $this->assertArrayHasKey('user_id', $item);
        $this->assertArrayHasKey('reason', $item);
        $this->assertArrayHasKey('created_at', $item);

        // No extra keys (no 'id', no profile fields)
        $this->assertArrayNotHasKey('id', $item);
        $this->assertArrayNotHasKey('name', $item);
        $this->assertArrayNotHasKey('email', $item);

        // Value types and shapes
        $this->assertSame((string) $task->id, $item['issue_id']);
        $this->assertSame('member', $item['user_type']);
        $this->assertSame('member-' . $assignee->id, $item['user_id']);
        $this->assertSame('assignee', $item['reason']);
        $this->assertIsString($item['created_at']);
        $this->assertNotEmpty($item['created_at']);
    }

    private function makeFixture(bool $withAssignee): array
    {
        $viewer = User::factory()->create();
        $assigneeUser = $withAssignee ? User::factory()->create() : null;

        $team = Team::query()->create([
            'name' => 'Sub Test Workspace',
            'lead_user_id' => $viewer->id,
        ]);

        $team->members()->attach(array_filter([$viewer->id, $assigneeUser?->id]));

        $project = Project::query()->create(['name' => 'Sub Test Project', 'status' => 'active']);
        $project->teams()->attach($team->id);

        $task = Task::query()->create([
            'project_id'  => $project->id,
            'title'       => 'Subscriber contract task',
            'type'        => 'feature',
            'priority'    => 'medium',
            'status'      => 'open',
            'assigned_to' => $assigneeUser?->id,
        ]);

        return ['viewer' => $viewer, 'task' => $task, 'assignee' => $assigneeUser];
    }
}
