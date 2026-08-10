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
use App\Models\TaskChangeRequest;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
            'status' => 'open',
            'assigned_to' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->patch(route('tasks.advance', $task))
            ->assertRedirect();

        $this->assertHistoryRow(1, $task->id, 'open', 'in-progress', 'user', $manager->id);

        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $task->id, 'in-review');

        $this->assertHistoryRow(2, $task->id, 'in-progress', 'in-review', 'user', $manager->id);

        // A reviewer requests changes (legal from in-review)
        $this->actingAs($manager)
            ->post(route('tasks.change-requests.store', $task), [
                'category' => 'Incomplete',
                'explanation' => 'Missing the edge case handling.',
                'status' => 'changes-requested',
            ])
            ->assertRedirect(route('tasks.show', $task));

        $this->assertHistoryRow(3, $task->id, 'in-review', 'changes-requested', 'user', $manager->id);

        // Only one history row — the change request links to it (no double-logging)
        $this->assertSame(
            1,
            TaskStatusHistory::where('task_id', $task->id)
                ->where('to_status', 'changes-requested')
                ->count()
        );
        $this->assertNotNull(
            TaskChangeRequest::where('task_id', $task->id)->firstOrFail()->task_status_history_id
        );

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

        $this->assertHistoryRow(4, $task->id, 'changes-requested', 'in-progress', 'daemon', null);

        $this->actingAs($manager)
            ->postJson("/api/daemon/tasks/{$queue->id}/complete")
            ->assertOk();

        $this->assertHistoryRow(5, $task->id, 'in-progress', 'in-review', 'daemon', null);

        $this->actingAs($manager)
            ->postJson("/api/daemon/tasks/{$queue->id}/fail", ['error' => 'daemon test'])
            ->assertOk();

        // Failing from review returns the task to in-progress so it can be retried
        $this->assertHistoryRow(6, $task->id, 'in-review', 'in-progress', 'daemon', null);
    }

    public function test_changes_requested_reason_is_required_and_persisted_for_reviewers(): void
    {
        $manager = $this->createUserWithRole('manager');
        $developer = $this->createUserWithRole('developer');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, [
            'status' => 'in-review',
            'assigned_to' => $developer->id,
        ]);

        $this->actingAs($developer)
            ->post(route('tasks.change-requests.store', $task), [
                'category' => 'Bug / broken',
                'explanation' => 'The implementation misses the spec.',
                'status' => 'changes-requested',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tasks.change-requests.store', $task), [
                'category' => 'Bug / broken',
                'explanation' => 'The implementation misses the spec.',
                'status' => 'changes-requested',
                'attachments' => [UploadedFile::fake()->image('screenshot.png')],
            ])
            ->assertRedirect(route('tasks.show', $task));

        $task->refresh();

        $this->assertSame('changes-requested', $task->status);

        $history = TaskStatusHistory::query()->where('task_id', $task->id)->latest()->firstOrFail();
        $changeRequest = TaskChangeRequest::query()->where('task_id', $task->id)->latest()->firstOrFail();

        $this->assertSame($history->id, $changeRequest->task_status_history_id);
        $this->assertSame('Bug / broken', $changeRequest->category);
        $this->assertSame('The implementation misses the spec.', $changeRequest->explanation);
        $this->assertFalse($changeRequest->getMedia('attachments')->isEmpty());

        $this->actingAs($developer)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSeeText('Changes Requested')
            ->assertSeeText('Bug / broken')
            ->assertSeeText('The implementation misses the spec.');
    }

    public function test_task_detail_view_displays_status_history_timeline(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'open']);

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
            'status' => 'open',
            'source' => 'manual',
            'estimated_hours' => 2,
        ], $attributes));
    }
}
