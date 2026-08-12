<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskChangeRequest;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\TaskStatusService;
use App\Support\Workflow\TransitionActor;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamAccountabilityTest extends TestCase
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

    public function test_manager_can_access_accountability_but_developer_cannot(): void
    {
        $manager = $this->createUserWithRole('manager');
        $developer = $this->createUserWithRole('developer');

        $this->actingAs($manager)
            ->get(route('team.accountability'))
            ->assertRedirect(route('teams.index', ['tab' => 'accountability']));

        $this->actingAs($manager)
            ->get(route('teams.index', ['tab' => 'accountability']))
            ->assertOk();

        $this->actingAs($developer)
            ->get(route('teams.index', ['tab' => 'accountability']))
            ->assertForbidden();
    }

    public function test_known_scenario_is_reported_correctly(): void
    {
        $manager = $this->createUserWithRole('manager');
        $developer = $this->createUserWithRole('developer');
        [, $project] = $this->createCustomerAndProject();

        $taskFirstTry = $this->createTask($project, [
            'assigned_to' => $developer->id,
            'status' => 'open',
            'title' => 'Task A',
        ]);

        $taskReturned = $this->createTask($project, [
            'assigned_to' => $developer->id,
            'status' => 'open',
            'title' => 'Task B',
        ]);

        $service = app(TaskStatusService::class);
        $actor = TransitionActor::user($manager);

        // First-time-right task: open -> in-progress -> in-review -> done
        $service->transition($taskFirstTry, 'in-progress', $actor);
        $service->transition($taskFirstTry, 'in-review', $actor);
        $service->transition($taskFirstTry, 'done', $actor);

        // Returned task: open -> in-progress -> in-review -> changes-requested -> in-progress -> in-review -> done
        $service->transition($taskReturned, 'in-progress', $actor);
        $service->transition($taskReturned, 'in-review', $actor);
        $history = $service->transition($taskReturned, 'changes-requested', input: [
            'category' => 'Bug / broken',
            'explanation' => 'Please fix this behavior.',
        ], actor: $actor);

        TaskChangeRequest::create([
            'task_id' => $taskReturned->id,
            'task_status_history_id' => $history?->id,
            'category' => 'Bug / broken',
            'explanation' => 'Please fix this behavior.',
        ]);

        $service->transition($taskReturned, 'in-progress', $actor);
        $service->transition($taskReturned, 'in-review', $actor);
        $service->transition($taskReturned, 'done', $actor);

        // Blocked checklist-gate attempt: in-progress -> in-review with unchecked checklist
        $blockedTask = $this->createTask($project, [
            'assigned_to' => $developer->id,
            'status' => 'in-progress',
            'title' => 'Task C',
        ]);
        TaskChecklist::create([
            'task_id' => $blockedTask->id,
            'label' => 'Unchecked item',
            'is_checked' => false,
            'sort_order' => 0,
        ]);

        try {
            $service->transition($blockedTask, 'in-review', $actor);
        } catch (\Throwable) {
            // Expected validation block.
        }

        $response = $this->actingAs($manager)->get(route('teams.index', ['tab' => 'accountability']));

        $response->assertOk();
        $response->assertSeeText($developer->name);
        $response->assertSeeText('2'); // completed count
        $response->assertSeeText('50%');
        $response->assertSeeText('Bug / broken: 1');

        // Drill-down should include both completed tasks with return details.
        $drilldown = $this->actingAs($manager)
            ->get(route('teams.index', ['tab' => 'accountability', 'developer_id' => $developer->id]));

        $drilldown->assertOk();
        $drilldown->assertSeeText('Task A');
        $drilldown->assertSeeText('Task B');
        $drilldown->assertSeeText('Bug / broken');

        $blockedCount = DB::table('activity_log')
            ->where('log_name', 'task-workflow')
            ->where('description', 'transition_blocked')
            ->where('properties->to_status', 'in-review')
            ->count();

        $this->assertGreaterThanOrEqual(1, $blockedCount);
        $this->assertGreaterThanOrEqual(1, TaskStatusHistory::where('task_id', $taskReturned->id)->where('to_status', 'changes-requested')->count());
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
