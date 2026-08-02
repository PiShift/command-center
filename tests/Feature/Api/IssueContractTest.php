<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IssueContractTest extends TestCase
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

    public function test_detail_and_list_return_the_same_multica_status_for_the_same_task(): void
    {
        ['viewer' => $viewer, 'task' => $task] = $this->makeIssueFixture();

        $detail = $this->actingAs($viewer)->getJson('/api/issues/' . $task->id);
        $list = $this->actingAs($viewer)->getJson('/api/issues?status=backlog');

        $detail->assertOk()->assertJsonPath('status', 'backlog');
        $list->assertOk()->assertJsonCount(1, 'issues');
        $this->assertSame('backlog', data_get($list->json(), 'issues.0.status'));
        $this->assertSame($detail->json('status'), data_get($list->json(), 'issues.0.status'));
    }

    public function test_put_accepts_raw_member_assignee_ids_and_returns_typed_assignee_ids(): void
    {
        ['viewer' => $viewer, 'task' => $task, 'assigneeA' => $assigneeA] = $this->makeIssueFixture();

        $update = $this->actingAs($viewer)->putJson('/api/issues/' . $task->id, [
            'assignee_type' => 'member',
            'assignee_id' => (string) $assigneeA->id,
        ]);

        $detail = $this->actingAs($viewer)->getJson('/api/issues/' . $task->id);
        $list = $this->actingAs($viewer)->getJson('/api/issues');

        $update->assertOk()
            ->assertJsonPath('assignee_type', 'member')
            ->assertJsonPath('assignee_id', 'member-' . $assigneeA->id);

        $detail->assertOk()
            ->assertJsonPath('assignee_type', 'member')
            ->assertJsonPath('assignee_id', 'member-' . $assigneeA->id);

        $list->assertOk();
        $this->assertSame('member', data_get($list->json(), 'issues.0.assignee_type'));
        $this->assertSame('member-' . $assigneeA->id, data_get($list->json(), 'issues.0.assignee_id'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $assigneeA->id,
            'agent_id' => null,
        ]);
    }

    public function test_prefixed_member_reassignment_keeps_creator_fields_stable(): void
    {
        ['viewer' => $viewer, 'task' => $task, 'assigneeA' => $assigneeA, 'assigneeB' => $assigneeB] = $this->makeIssueFixture();

        $task->forceFill(['assigned_to' => $assigneeA->id])->save();

        $update = $this->actingAs($viewer)->putJson('/api/issues/' . $task->id, [
            'assignee_type' => 'member',
            'assignee_id' => 'member-' . $assigneeB->id,
        ]);

        $detail = $this->actingAs($viewer)->getJson('/api/issues/' . $task->id);
        $list = $this->actingAs($viewer)->getJson('/api/issues');

        $update->assertOk()
            ->assertJsonPath('assignee_id', 'member-' . $assigneeB->id)
            ->assertJsonPath('creator_type', 'member')
            ->assertJsonPath('creator_id', 'member-' . $assigneeB->id);

        $detail->assertOk()
            ->assertJsonPath('assignee_id', 'member-' . $assigneeB->id)
            ->assertJsonPath('creator_type', 'member')
            ->assertJsonPath('creator_id', 'member-' . $assigneeB->id);

        $list->assertOk();
        $this->assertSame('member-' . $assigneeB->id, data_get($list->json(), 'issues.0.assignee_id'));
        $this->assertSame('member', data_get($list->json(), 'issues.0.creator_type'));
        $this->assertSame('member-' . $assigneeB->id, data_get($list->json(), 'issues.0.creator_id'));
    }

    public function test_status_todo_filter_returns_non_null_creator_and_enum_safe_payload(): void
    {
        ['viewer' => $viewer, 'task' => $task] = $this->makeIssueFixture();

        $task->forceFill([
            'status' => 'open',
            'priority' => 'medium',
            'assigned_to' => null,
        ])->save();

        $response = $this->actingAs($viewer)->getJson('/api/issues?status=todo');

        $response->assertOk()->assertJsonCount(1, 'issues');

        $issue = data_get($response->json(), 'issues.0');

        $this->assertNotNull($issue['creator_type']);
        $this->assertNotNull($issue['creator_id']);
        $this->assertSame('member', $issue['creator_type']);
        $this->assertSame('member-' . $viewer->id, $issue['creator_id']);

        $this->assertContains($issue['status'], ['backlog', 'in_progress', 'in_review', 'blocked', 'done', 'cancelled']);
        $this->assertContains($issue['priority'], ['low', 'medium', 'high']);
        $this->assertContains($issue['assignee_type'], ['member', 'agent', 'squad', null]);

        if ($issue['assignee_type'] !== null) {
            $this->assertIsString($issue['assignee_id']);
            $this->assertMatchesRegularExpression('/^(member|agent|squad)-.+$/', $issue['assignee_id']);
        }
    }

    private function makeIssueFixture(): array
    {
        $viewer = User::factory()->create();
        $assigneeA = User::factory()->create();
        $assigneeB = User::factory()->create();

        $team = Team::query()->create([
            'name' => 'Workspace Alpha',
            'lead_user_id' => $viewer->id,
        ]);

        $team->members()->attach([
            $viewer->id,
            $assigneeA->id,
            $assigneeB->id,
        ]);

        $project = Project::query()->create([
            'name' => 'Desktop Contract',
            'status' => 'active',
        ]);

        $project->teams()->attach($team->id);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Normalize issue contract',
            'description' => 'Contract fixture',
            'type' => 'feature',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        return compact('viewer', 'assigneeA', 'assigneeB', 'team', 'project', 'task');
    }
}