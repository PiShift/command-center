<?php

namespace Tests\Feature;

use App\Exceptions\InvalidTaskTransition;
use App\Http\Middleware\DaemonTokenMiddleware;
use App\Http\Middleware\RequiresTwoFactor;
use App\Livewire\KanbanBoard;
use App\Livewire\TaskModal;
use App\Models\AgentRuntime;
use App\Models\AgentTaskQueue;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\TaskStatusService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskStatusWorkflowTest extends TestCase
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

    // ── Legality ────────────────────────────────────────────────────────────

    public function test_illegal_transition_is_rejected_by_the_service(): void
    {
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'open']);

        try {
            app(TaskStatusService::class)->transition($task, 'done');
            $this->fail('Expected InvalidTaskTransition');
        } catch (InvalidTaskTransition $e) {
            $this->assertSame('legality', $e->stage);
        }

        $this->assertSame('open', $task->fresh()->status);
        $this->assertSame(0, TaskStatusHistory::count());
    }

    public function test_illegal_transition_is_rejected_on_every_path(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();

        // Board: open → done is illegal — user gets a toast and the card snaps back
        $boardTask = $this->createTask($project, ['status' => 'open', 'assigned_to' => $manager->id]);
        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $boardTask->id, 'done')
            ->assertDispatched('board-toast', fn (string $name, array $params) => ($params['type'] ?? null) === 'error'
                && ($params['taskId'] ?? null) === $boardTask->id
                && str_contains((string) ($params['message'] ?? ''), 'not allowed'));
        $this->assertSame('open', $boardTask->fresh()->status);

        // Modal: open → in-review is illegal — error shown and select reverts
        $modalTask = $this->createTask($project, ['status' => 'open', 'assigned_to' => $manager->id]);
        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $modalTask->id)
            ->set('status', 'in-review')
            ->call('saveField', 'status')
            ->assertHasErrors('status')
            ->assertSet('status', 'open');
        $this->assertSame('open', $modalTask->fresh()->status);

        // Full form update: open → in-review rejected with errors
        $formTask = $this->createTask($project, ['status' => 'open']);
        $this->actingAs($manager)
            ->put(route('tasks.update', $formTask), $this->formPayload($formTask, 'in-review'))
            ->assertSessionHasErrors('status');
        $this->assertSame('open', $formTask->fresh()->status);

        // Daemon start on a task that is already done: illegal
        $daemonTask = $this->createTask($project, ['status' => 'done']);
        $queue = $this->createQueueFor($daemonTask, $manager);
        $this->withoutMiddleware(DaemonTokenMiddleware::class);
        $this->actingAs($manager)
            ->postJson("/api/daemon/tasks/{$queue->id}/start")
            ->assertStatus(422);
        $this->assertSame('done', $daemonTask->fresh()->status);

        // API issue update: done → todo is illegal
        $this->actingAs($manager)
            ->putJson('/api/issues/'.$daemonTask->id, ['status' => 'todo'])
            ->assertStatus(422);
        $this->assertSame('done', $daemonTask->fresh()->status);
    }

    // ── Checklist validator on every path ───────────────────────────────────

    public function test_in_review_is_blocked_by_unchecked_checklist_on_all_four_paths(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();

        // 1. Board drag — blocked with a visible toast naming the unchecked item
        $task = $this->taskInProgressWithUncheckedItem($project, $manager);
        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $task->id, 'in-review')
            ->assertDispatched('board-toast', fn (string $name, array $params) => ($params['type'] ?? null) === 'error'
                && str_contains((string) ($params['message'] ?? ''), 'Write tests'));
        $this->assertSame('in-progress', $task->fresh()->status);

        // 2. Task modal status field
        $task = $this->taskInProgressWithUncheckedItem($project, $manager);
        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $task->id)
            ->set('status', 'in-review')
            ->call('saveField', 'status')
            ->assertHasErrors('status');
        $this->assertSame('in-progress', $task->fresh()->status);

        // 3. Advance action
        $task = $this->taskInProgressWithUncheckedItem($project, $manager);
        $this->actingAs($manager)
            ->patch(route('tasks.advance', $task))
            ->assertSessionHas('error');
        $this->assertSame('in-progress', $task->fresh()->status);

        // 4. Simulated daemon/agent call
        $task = $this->taskInProgressWithUncheckedItem($project, $manager);
        $queue = $this->createQueueFor($task, $manager);
        $this->withoutMiddleware(DaemonTokenMiddleware::class);
        $this->actingAs($manager)
            ->postJson("/api/daemon/tasks/{$queue->id}/complete")
            ->assertStatus(422);
        $this->assertSame('in-progress', $task->fresh()->status);

        // The error message lists the unchecked item
        try {
            app(TaskStatusService::class)->transition($task->fresh(), 'in-review');
            $this->fail('Expected InvalidTaskTransition');
        } catch (InvalidTaskTransition $e) {
            $this->assertSame('validator', $e->stage);
            $this->assertStringContainsString('Write tests', $e->getMessage());
        }
    }

    public function test_tasks_with_no_or_checked_checklist_items_enter_review_normally(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();

        // Zero checklist items — unaffected by the validator
        $noChecklist = $this->createTask($project, ['status' => 'in-progress', 'assigned_to' => $manager->id]);
        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $noChecklist->id, 'in-review');
        $this->assertSame('in-review', $noChecklist->fresh()->status);

        // All items checked — passes
        $checked = $this->taskInProgressWithUncheckedItem($project, $manager);
        $checked->checklists()->update(['is_checked' => true]);
        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $checked->id, 'in-review');
        $this->assertSame('in-review', $checked->fresh()->status);
    }

    // ── Conditions ──────────────────────────────────────────────────────────

    public function test_done_reopen_is_manager_only(): void
    {
        [, $project] = $this->createCustomerAndProject();

        $service = app(TaskStatusService::class);

        $developer = $this->createUserWithRole('developer');
        $task = $this->createTask($project, ['status' => 'done', 'assigned_to' => $developer->id]);

        try {
            $service->transition($task, 'in-progress', \App\Support\Workflow\TransitionActor::user($developer));
            $this->fail('Expected InvalidTaskTransition');
        } catch (InvalidTaskTransition $e) {
            $this->assertSame('condition', $e->stage);
        }

        $manager = $this->createUserWithRole('manager');
        $service->transition($task->fresh(), 'in-progress', \App\Support\Workflow\TransitionActor::user($manager));

        $fresh = $task->fresh();
        $this->assertSame('in-progress', $fresh->status);
        // Reopening clears completed_at
        $this->assertNull($fresh->completed_at);
    }

    public function test_changes_requested_condition_requires_reviewer_permission(): void
    {
        [, $project] = $this->createCustomerAndProject();
        $developer = $this->createUserWithRole('developer');
        $task = $this->createTask($project, ['status' => 'in-review', 'assigned_to' => $developer->id]);

        // Even if a developer could reach the endpoint, the service condition blocks them
        try {
            app(TaskStatusService::class)->transition(
                $task,
                'changes-requested',
                \App\Support\Workflow\TransitionActor::user($developer),
            );
            $this->fail('Expected InvalidTaskTransition');
        } catch (InvalidTaskTransition $e) {
            $this->assertSame('condition', $e->stage);
        }

        $this->assertSame('in-review', $task->fresh()->status);
    }

    // ── Post-functions ──────────────────────────────────────────────────────

    public function test_reaching_done_sets_completed_at_and_logs_history(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'in-review', 'assigned_to' => $manager->id]);

        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $task->id, 'done');

        $fresh = $task->fresh();
        $this->assertSame('done', $fresh->status);
        $this->assertNotNull($fresh->completed_at);

        $history = TaskStatusHistory::where('task_id', $task->id)->latest('id')->firstOrFail();
        $this->assertSame('in-review', $history->from_status);
        $this->assertSame('done', $history->to_status);
        $this->assertSame('user', $history->actor_type);
        $this->assertSame($manager->id, $history->actor_user_id);
    }

    public function test_advance_walks_the_legal_path(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'open']);

        // open → in-progress → in-review → done
        foreach (['in-progress', 'in-review', 'done'] as $expected) {
            $this->actingAs($manager)->patch(route('tasks.advance', $task));
            $this->assertSame($expected, $task->fresh()->status);
        }

        // done → in-progress (reopen)
        $this->actingAs($manager)->patch(route('tasks.advance', $task));
        $this->assertSame('in-progress', $task->fresh()->status);

        // changes-requested has no generic advance
        $task->forceFill(['status' => 'changes-requested'])->save();
        $this->actingAs($manager)
            ->patch(route('tasks.advance', $task))
            ->assertSessionHas('error');
        $this->assertSame('changes-requested', $task->fresh()->status);
    }

    // ── Required input (changes-requested) ─────────────────────────────────

    public function test_changes_requested_requires_category_and_explanation_everywhere(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $service = app(TaskStatusService::class);

        // Direct service call without input (as a manager — passes the
        // reviewer condition, then fails on the missing required input)
        $actor = \App\Support\Workflow\TransitionActor::user($manager);
        $task = $this->createTask($project, ['status' => 'in-review', 'assigned_to' => $manager->id]);
        try {
            $service->transition($task, 'changes-requested', $actor);
            $this->fail('Expected InvalidTaskTransition');
        } catch (InvalidTaskTransition $e) {
            $this->assertSame('validator', $e->stage);
            $this->assertStringContainsString('reason', $e->getMessage());
        }
        $this->assertSame('in-review', $task->fresh()->status);

        // Service call WITH input succeeds and creates the change request
        $service->transition(
            $task->fresh(),
            'changes-requested',
            $actor,
            input: ['category' => 'Incomplete', 'explanation' => 'Missing the empty state.'],
            postPersist: function (Task $task, ?TaskStatusHistory $history): void {
                \App\Models\TaskChangeRequest::query()->create([
                    'task_id' => $task->id,
                    'task_status_history_id' => $history?->id,
                    'category' => 'Incomplete',
                    'explanation' => 'Missing the empty state.',
                ]);
            },
        );
        $this->assertSame('changes-requested', $task->fresh()->status);
        $this->assertDatabaseHas('task_change_requests', [
            'task_id' => $task->id,
            'category' => 'Incomplete',
            'explanation' => 'Missing the empty state.',
        ]);
    }

    public function test_board_drag_into_changes_requested_is_blocked_with_guidance(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'in-review', 'assigned_to' => $manager->id]);

        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $task->id, 'changes-requested')
            ->assertDispatched('board-toast', fn (string $name, array $params) => ($params['type'] ?? null) === 'error'
                && str_contains((string) ($params['message'] ?? ''), 'Request changes'));

        $this->assertSame('in-review', $task->fresh()->status);
    }

    public function test_task_modal_request_changes_dialog_flow(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'in-review', 'assigned_to' => $manager->id]);
        TaskChecklist::create(['task_id' => $task->id, 'label' => 'Write tests', 'is_checked' => true, 'sort_order' => 0]);

        // Missing category → validation error, no transition
        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $task->id)
            ->set('changeExplanation', 'Needs error handling on empty states.')
            ->call('requestChanges')
            ->assertHasErrors('changeCategory');
        $this->assertSame('in-review', $task->fresh()->status);

        // Complete input → transition + change request + checklist reset
        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $task->id)
            ->set('changeCategory', 'Incomplete')
            ->set('changeExplanation', 'Needs error handling on empty states.')
            ->call('requestChanges')
            ->assertDispatched('changes-requested');

        $fresh = $task->fresh();
        $this->assertSame('changes-requested', $fresh->status);
        $this->assertDatabaseHas('task_change_requests', [
            'task_id' => $task->id,
            'category' => 'Incomplete',
        ]);
        // Checklist was reset — dev must re-confirm
        $this->assertSame(0, $fresh->checklists()->where('is_checked', true)->count());
    }

    // ── Checklist reset on rework ───────────────────────────────────────────

    public function test_rework_transitions_reset_checklist_items(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $service = app(TaskStatusService::class);
        $actor = \App\Support\Workflow\TransitionActor::user($manager);

        // in-review → changes-requested resets
        $task = $this->createTask($project, ['status' => 'in-review']);
        TaskChecklist::create(['task_id' => $task->id, 'label' => 'A', 'is_checked' => true, 'sort_order' => 0]);
        $service->transition($task, 'changes-requested', $actor, ['category' => 'Other', 'explanation' => 'Rework needed.']);
        $this->assertSame(0, $task->checklists()->where('is_checked', true)->count());

        // changes-requested → in-progress: already unchecked, stays unchecked (idempotent)
        $service->transition($task->fresh(), 'in-progress', $actor);
        $this->assertSame('in-progress', $task->fresh()->status);

        // in-review → done does NOT reset (it's completion, not rework)
        $task2 = $this->createTask($project, ['status' => 'in-review']);
        TaskChecklist::create(['task_id' => $task2->id, 'label' => 'B', 'is_checked' => true, 'sort_order' => 0]);
        $service->transition($task2, 'done', $actor);
        $this->assertSame(1, $task2->checklists()->where('is_checked', true)->count());

        // done → in-progress (reopen) resets — the DoD must be re-confirmed
        $service->transition($task2->fresh(), 'in-progress', $actor);
        $this->assertSame(0, $task2->checklists()->where('is_checked', true)->count());
    }

    public function test_daemon_failure_does_not_reset_checklists(): void
    {
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'in-review']);
        TaskChecklist::create(['task_id' => $task->id, 'label' => 'A', 'is_checked' => true, 'sort_order' => 0]);

        app(TaskStatusService::class)->transition(
            $task,
            'in-progress',
            \App\Support\Workflow\TransitionActor::agent(null, 'Daemon runtime'),
        );

        // A daemon retry is not rework — items stay checked
        $this->assertSame(1, $task->checklists()->where('is_checked', true)->count());
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function taskInProgressWithUncheckedItem(Project $project, User $manager): Task
    {
        $task = $this->createTask($project, ['status' => 'in-progress', 'assigned_to' => $manager->id]);
        TaskChecklist::create([
            'task_id' => $task->id,
            'label' => 'Write tests',
            'is_checked' => false,
            'sort_order' => 0,
        ]);

        return $task;
    }

    private function createQueueFor(Task $task, User $owner): AgentTaskQueue
    {
        $runtime = AgentRuntime::create([
            'user_id' => $owner->id,
            'daemon_id' => 'daemon-'.fake()->unique()->numberBetween(1, 99999),
            'name' => 'Test Runtime',
            'provider' => 'copilot',
            'status' => 'online',
        ]);

        return AgentTaskQueue::create([
            'task_id' => $task->id,
            'runtime_id' => $runtime->id,
            'status' => 'queued',
            'prompt' => 'Execute task',
        ]);
    }

    private function formPayload(Task $task, string $status): array
    {
        return [
            'title' => $task->title,
            'project_id' => $task->project_id,
            'assigned_to' => $task->assigned_to,
            'agent_id' => null,
            'type' => $task->type,
            'priority' => $task->priority,
            'status' => $status,
            'due_date' => null,
            'estimated_hours' => $task->estimated_hours,
            'labels' => [],
            'description' => $task->description,
            'source' => $task->source,
            'original_input' => $task->original_input,
            'guide' => $task->guide,
        ];
    }

    private function createUserWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }

    private function createCustomerAndProject(string $customerName = 'Acme Corp'): array
    {
        $customer = Customer::create([
            'name' => $customerName,
            'email' => fake()->unique()->safeEmail(),
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'name' => $customerName.' Project',
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
        ], $attributes));
    }
}
