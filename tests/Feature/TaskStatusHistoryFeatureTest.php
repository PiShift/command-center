<?php

namespace Tests\Feature;

use App\Http\Middleware\DaemonTokenMiddleware;
use App\Http\Middleware\RequiresTwoFactor;
use App\Livewire\KanbanBoard;
use App\Models\AgentRuntime;
use App\Models\AgentTaskQueue;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskStatusHistoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RequiresTwoFactor::class);
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }

    public function test_every_supported_status_change_path_creates_one_history_row(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, [
            'status' => 'backlog',
            'assigned_to' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->patch(route('tasks.advance', $task))
            ->assertRedirect();

        $this->assertHistoryRow(1, $task->id, 'backlog', 'in-progress', 'user', $manager->id);

        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $task->id, 'done');

        $this->assertHistoryRow(2, $task->id, 'in-progress', 'done', 'user', $manager->id);

        $this->actingAs($manager)
            ->put(route('tasks.update', $task), [
                'title' => $task->title,
                'project_id' => $task->project_id,
                'assigned_to' => $task->assigned_to,
                'agent_id' => null,
                'type' => $task->type,
                'priority' => $task->priority,
                'status' => 'backlog',
                'due_date' => null,
                'estimated_hours' => $task->estimated_hours,
                'labels' => [],
                'description' => $task->description,
                'source' => $task->source,
                'original_input' => $task->original_input,
                'guide' => $task->guide,
            ])
            ->assertRedirect(route('tasks.show', $task));

        $this->assertHistoryRow(3, $task->id, 'done', 'backlog', 'user', $manager->id);

        $runtime = AgentRuntime::create([
            'user_id' => $manager->id,
            'daemon_id' => 'daemon-test',
            'name' => 'Test Runtime',
            'provider' => 'copilot',
            'status' => 'online',
        ]);

        $queue = AgentTaskQueue::create([
            'task_id' => $task->id,
            'runtime_id' => $runtime->id,
            'status' => 'queued',
            'prompt' => 'Execute task',
        ]);

        $this->withoutMiddleware(DaemonTokenMiddleware::class);

        $this->actingAs($manager)
            ->postJson("/api/daemon/tasks/{$queue->id}/start")
            ->assertOk();

        $this->assertHistoryRow(4, $task->id, 'backlog', 'in-progress', 'daemon', null);

        $this->actingAs($manager)
            ->postJson("/api/daemon/tasks/{$queue->id}/complete")
            ->assertOk();

        $this->assertHistoryRow(5, $task->id, 'in-progress', 'in-review', 'daemon', null);

        $this->actingAs($manager)
            ->postJson("/api/daemon/tasks/{$queue->id}/fail", ['error' => 'daemon test'])
            ->assertOk();

        $this->assertHistoryRow(6, $task->id, 'in-review', 'open', 'daemon', null);
    }

    public function test_task_detail_view_displays_status_history_timeline(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'backlog']);

        $this->actingAs($manager)
            ->patch(route('tasks.advance', $task))
            ->assertRedirect();

        TaskStatusHistory::query()->create([
            'task_id' => $task->id,
            'from_status' => 'in-progress',
            'to_status' => 'in-review',
            'actor_type' => 'daemon',
            'actor_label' => 'Daemon runtime',
        ]);

        $this->actingAs($manager)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSeeText('Status History')
            ->assertSeeText($manager->name)
            ->assertSeeText('Daemon runtime')
            ->assertSeeText('moved from')
            ->assertSeeText('to');
    }

    private function assertHistoryRow(
        int $expectedCount,
        int $taskId,
        string $fromStatus,
        string $toStatus,
        string $actorType,
        ?int $actorUserId,
    ): void {
        $this->assertSame($expectedCount, TaskStatusHistory::query()->count());

        $entry = TaskStatusHistory::query()->latest('id')->firstOrFail();

        $this->assertSame($taskId, $entry->task_id);
        $this->assertSame($fromStatus, $entry->from_status);
        $this->assertSame($toStatus, $entry->to_status);
        $this->assertSame($actorType, $entry->actor_type);
        $this->assertSame($actorUserId, $entry->actor_user_id);
    }

    private function createUserWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }

    private function createCustomerAndProject(): array
    {
        $customer = Customer::create([
            'name' => 'Acme Corp',
            'email' => 'billing@example.test',
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'name' => 'Client Project',
            'status' => 'active',
        ]);

        return [$customer, $project];
    }

    private function createTask(Project $project, array $attributes = []): Task
    {
        return Task::create(array_merge([
            'project_id' => $project->id,
            'title' => 'Task '.fake()->words(2, true),
            'type' => 'feature',
            'priority' => 'medium',
            'status' => 'backlog',
            'source' => 'manual',
            'estimated_hours' => 2,
        ], $attributes));
    }
}
