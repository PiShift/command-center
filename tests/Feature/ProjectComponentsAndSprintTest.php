<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Livewire\TaskModal;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskComponent;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectComponentsAndSprintTest extends TestCase
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

    // ── Global components settings ──────────────────────────────────────────

    public function test_manager_can_manage_global_components(): void
    {
        $manager = $this->createUserWithRole('manager');

        $this->actingAs($manager)
            ->post(route('settings.task-components.store'), ['name' => 'Backend'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_components', ['name' => 'Backend']);

        $component = TaskComponent::where('name', 'Backend')->firstOrFail();

        $this->actingAs($manager)
            ->patch(route('settings.task-components.update', $component), ['name' => 'API'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_components', ['id' => $component->id, 'name' => 'API']);
    }

    public function test_developers_cannot_manage_global_components(): void
    {
        $developer = $this->createUserWithRole('developer');

        $this->actingAs($developer)
            ->get(route('settings.task-components.index'))
            ->assertForbidden();
    }

    public function test_manual_bulk_reassign_updates_only_selected_legacy_value(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $target = TaskComponent::create(['name' => 'Mobile', 'sort_order' => 99]);

        $legacyTask = $this->createTask($project, ['component' => 'iOS']);
        $otherTask = $this->createTask($project, ['component' => 'Backend']);

        $this->actingAs($manager)
            ->post(route('settings.task-components.bulk-reassign'), [
                'from_component' => 'iOS',
                'to_component_id' => $target->id,
            ])
            ->assertRedirect();

        $this->assertSame('Mobile', $legacyTask->fresh()->component);
        $this->assertSame('Backend', $otherTask->fresh()->component);
    }

    // ── Sprint field on the board Task Modal ────────────────────────────────

    public function test_task_modal_saves_sprint_scoped_to_project(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('newTask')
            ->set('title', 'Sprint task')
            ->set('projectId', $project->id)
            ->set('sprintId', $sprint->id)
            ->call('saveNew');

        $task = Task::where('title', 'Sprint task')->firstOrFail();
        $this->assertSame($sprint->id, $task->sprint_id);
    }

    public function test_changing_project_clears_sprint_from_other_project(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        [, $otherProject] = $this->createCustomerAndProject('Other Corp');
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
            'sort_order' => 0,
        ]);
        $task = $this->createTask($project, ['sprint_id' => $sprint->id]);

        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $task->id)
            ->set('projectId', $otherProject->id)
            ->call('saveField', 'projectId');

        $fresh = $task->fresh();
        $this->assertSame($otherProject->id, $fresh->project_id);
        $this->assertNull($fresh->sprint_id);
    }

    // ── Null-sprint degradation ─────────────────────────────────────────────

    public function test_available_to_claim_includes_open_unassigned_null_sprint_tasks(): void
    {
        // A manager-scoped permissions set WITHOUT projects.manage, so the
        // project page renders the non-manager branch (canManage = false).
        $role = Role::create([
            'slug' => 'lead',
            'name' => 'Team Lead',
            'color' => '#3a6fba',
            'description' => 'Sees everything, does not manage projects.',
        ]);
        $role->permissions()->sync(
            \App\Models\Permission::whereIn('slug', ['tasks.view', 'tasks.view_all', 'projects.view', 'projects.view_all'])->pluck('id')
        );
        $user = User::factory()->create(['role_id' => $role->id]);
        [, $project] = $this->createCustomerAndProject();

        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
            'sort_order' => 0,
        ]);

        $inSprint = $this->createTask($project, [
            'title' => 'In sprint',
            'status' => 'open',
            'sprint_id' => $sprint->id,
        ]);
        $noSprint = $this->createTask($project, [
            'title' => 'No sprint',
            'status' => 'open',
            'sprint_id' => null,
        ]);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertOk();
        $availableIds = collect($response->viewData('availableTasks'))->pluck('id');
        $this->assertTrue($availableIds->contains($inSprint->id));
        $this->assertTrue($availableIds->contains($noSprint->id));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

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
            'status' => 'backlog',
            'source' => 'manual',
        ], $attributes));
    }
}
