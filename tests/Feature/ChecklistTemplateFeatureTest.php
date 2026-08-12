<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Livewire\KanbanBoard;
use App\Livewire\ProjectBacklog;
use App\Livewire\ProjectSprints;
use App\Livewire\TaskModal;
use App\Models\BacklogItem;
use App\Models\ChecklistTemplate;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComponent;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChecklistTemplateFeatureTest extends TestCase
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

    // ── Template CRUD access ────────────────────────────────────────────────

    public function test_managers_can_manage_templates_and_developers_cannot(): void
    {
        $manager = $this->createUserWithRole('manager');
        $developer = $this->createUserWithRole('developer');
        [, $project] = $this->createCustomerAndProject();

        $this->actingAs($developer)
            ->get(route('checklist-templates.index'))
            ->assertForbidden();

        $this->actingAs($developer)
            ->post(route('checklist-templates.store'), [
                'name' => 'Nope',
                'items' => 'One',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('checklist-templates.store'), [
                'name' => 'Web Feature DoD',
                'project_id' => $project->id,
                'type' => 'feature',
                'items' => "Design reviewed\nTests written\nTests written\n",
            ])
            ->assertRedirect(route('checklist-templates.index'));

        $template = ChecklistTemplate::where('name', 'Web Feature DoD')->firstOrFail();
        $this->assertSame($project->id, $template->project_id);
        $this->assertSame('feature', $template->type);
        $this->assertSame(['Design reviewed', 'Tests written'], $template->items->pluck('label')->all());

        $this->actingAs($manager)
            ->put(route('checklist-templates.update', $template), [
                'name' => 'Web Feature DoD v2',
                'project_id' => null,
                'type' => null,
                'items' => "Only item",
            ])
            ->assertRedirect(route('checklist-templates.index'));

        $this->assertNull($template->fresh()->project_id);
        $this->assertSame(['Only item'], $template->items()->pluck('label')->all());

        $this->actingAs($manager)
            ->delete(route('checklist-templates.destroy', $template))
            ->assertRedirect(route('checklist-templates.index'));

        $this->assertDatabaseMissing('checklist_templates', ['id' => $template->id]);
    }

    // ── Auto-attach across creation paths ───────────────────────────────────

    public function test_task_modal_creation_attaches_matching_templates_with_dedupe(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        [, $otherProject] = $this->createCustomerAndProject('Other Corp');

        $this->createTemplate(null, null, ['Universal A', 'Shared item']);
        $this->createTemplate($project->id, 'feature', ['Shared item', 'Feature only']);
        $this->createTemplate($project->id, 'bug', ['Bug only']);
        $this->createTemplate($otherProject->id, null, ['Other project only']);

        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('newTask')
            ->set('title', 'New feature task')
            ->set('type', 'feature')
            ->set('projectId', $project->id)
            ->call('saveNew');

        $task = Task::where('title', 'New feature task')->firstOrFail();

        $this->assertSame(
            ['Universal A', 'Shared item', 'Feature only'],
            $task->checklists()->orderBy('sort_order')->pluck('label')->all()
        );

        $this->assertTrue($task->checklists->every->isLocked());
    }

    public function test_task_controller_store_attaches_templates(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $this->createTemplate(null, null, ['Baseline item']);

        $this->actingAs($manager)
            ->post(route('tasks.store'), [
                'title' => 'Classic form task',
                'project_id' => $project->id,
                'type' => 'change',
                'priority' => 'medium',
                'status' => 'open',
                'source' => 'manual',
            ])
            ->assertRedirect();

        $task = Task::where('title', 'Classic form task')->firstOrFail();
        $this->assertSame(['Baseline item'], $task->checklists->pluck('label')->all());
    }

    public function test_backlog_promote_paths_attach_templates(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $this->createTemplate($project->id, 'bug', ['Bug baseline']);

        $backlogItem = $project->backlogItems()->create([
            'title' => 'Fix the thing',
            'status' => 'raw',
            'promoted' => false,
            'sort_order' => 1,
        ]);

        // HTTP controller path
        $this->actingAs($manager)
            ->post(route('backlog.promote', [$project, $backlogItem]), [
                'title' => 'Fix the thing',
                'type' => 'bug',
                'priority' => 'high',
                'weight' => 3,
            ])
            ->assertRedirect();

        $task = Task::where('title', 'Fix the thing')->firstOrFail();
        $this->assertSame(['Bug baseline'], $task->checklists->pluck('label')->all());

        // Livewire ProjectBacklog path
        $secondItem = $project->backlogItems()->create([
            'title' => 'Fix another thing',
            'status' => 'raw',
            'promoted' => false,
            'sort_order' => 2,
        ]);

        Livewire::actingAs($manager)
            ->test(ProjectBacklog::class, ['project' => $project])
            ->call('openPromote', $secondItem->id)
            ->set('promoteType', 'bug')
            ->call('promoteItem');

        $secondTask = Task::where('title', 'Fix another thing')->firstOrFail();
        $this->assertSame(['Bug baseline'], $secondTask->checklists->pluck('label')->all());

        // Bulk promote path
        $thirdItem = $project->backlogItems()->create([
            'title' => 'Bulk promoted feature',
            'status' => 'raw',
            'promoted' => false,
            'sort_order' => 3,
        ]);

        $this->actingAs($manager)
            ->post(route('backlog.bulk-promote', $project), ['items' => [$thirdItem->id]])
            ->assertRedirect();

        $thirdTask = Task::where('title', 'Bulk promoted feature')->firstOrFail();
        // bulkPromote creates type=feature, so the bug template must not apply
        $this->assertCount(0, $thirdTask->checklists);
    }

    public function test_sprint_quick_add_attaches_templates(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $this->createTemplate(null, 'feature', ['Feature baseline']);

        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($manager)
            ->test(ProjectSprints::class, ['project' => $project])
            ->call('showAddTask', $sprint->id)
            ->set('addTaskTitle', 'Quick task')
            ->call('createTask');

        $task = Task::where('title', 'Quick task')->firstOrFail();
        $this->assertSame(['Feature baseline'], $task->checklists->pluck('label')->all());
    }

    // ── Locked vs free items ────────────────────────────────────────────────

    public function test_template_items_cannot_be_deleted_but_manual_items_can(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        $this->createTemplate(null, null, ['Locked item']);

        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('newTask')
            ->set('title', 'Task with locked items')
            ->set('projectId', $project->id)
            ->call('saveNew');

        $task = Task::where('title', 'Task with locked items')->firstOrFail();
        $lockedItem = $task->checklists->first();
        $manualItem = TaskChecklist::create([
            'task_id' => $task->id,
            'label' => 'My own item',
            'is_checked' => false,
            'sort_order' => 99,
        ]);

        // Livewire modal path
        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $task->id)
            ->call('deleteChecklistItem', $lockedItem->id)
            ->call('deleteChecklistItem', $manualItem->id);

        $this->assertDatabaseHas('task_checklists', ['id' => $lockedItem->id]);
        $this->assertDatabaseMissing('task_checklists', ['id' => $manualItem->id]);

        // HTTP controller path (used by the task show page)
        $manualItem2 = TaskChecklist::create([
            'task_id' => $task->id,
            'label' => 'Another own item',
            'is_checked' => false,
            'sort_order' => 100,
        ]);

        $this->actingAs($manager)
            ->delete(route('checklists.destroy', [$task, $lockedItem]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('checklists.destroy', [$task, $manualItem2]))
            ->assertRedirect();

        $this->assertDatabaseHas('task_checklists', ['id' => $lockedItem->id]);
        $this->assertDatabaseMissing('task_checklists', ['id' => $manualItem2->id]);

        // Locked items can still be checked off
        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $task->id)
            ->call('toggleChecklistItem', $lockedItem->id);

        $this->assertTrue($lockedItem->fresh()->is_checked);
    }

    // ── Existing tasks unaffected ───────────────────────────────────────────

    public function test_existing_tasks_are_not_modified_retroactively(): void
    {
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, ['status' => 'open']);

        $this->createTemplate($project->id, null, ['Late baseline']);

        $this->assertCount(0, $task->fresh()->checklists);
    }

    // ── Component field ─────────────────────────────────────────────────────

    public function test_global_components_are_manager_configurable(): void
    {
        $manager = $this->createUserWithRole('manager');

        $this->actingAs($manager)
            ->post(route('task-components.store'), ['name' => 'Mobile'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_components', ['name' => 'Mobile']);
    }

    public function test_task_modal_saves_component_and_labels(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        TaskComponent::create(['name' => 'Mobile', 'sort_order' => 1]);
        TaskComponent::create(['name' => 'Backend', 'sort_order' => 2]);

        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('newTask')
            ->set('title', 'Component task')
            ->set('projectId', $project->id)
            ->set('component', 'Mobile')
            ->call('addLabel', 'urgent')
            ->call('addLabel', 'ios')
            ->call('saveNew');

        $task = Task::where('title', 'Component task')->firstOrFail();
        $this->assertSame('Mobile', $task->component);
        $this->assertSame(['urgent', 'ios'], $task->labels);

        // Changing the project keeps the component (global list, not project-scoped)
        [, $otherProject] = $this->createCustomerAndProject('Other Corp');

        Livewire::actingAs($manager)
            ->test(TaskModal::class)
            ->call('openTask', $task->id)
            ->set('projectId', $otherProject->id)
            ->call('saveField', 'projectId');

        $this->assertSame('Mobile', $task->fresh()->component);
    }

    // ── Board filtering ─────────────────────────────────────────────────────

    public function test_board_filters_by_component_and_label(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();
        TaskComponent::create(['name' => 'Mobile', 'sort_order' => 1]);
        TaskComponent::create(['name' => 'Backend', 'sort_order' => 2]);

        $mobileTask = $this->createTask($project, [
            'title' => 'Mobile task',
            'status' => 'open',
            'component' => 'Mobile',
            'labels' => ['urgent'],
        ]);
        $backendTask = $this->createTask($project, [
            'title' => 'Backend task',
            'status' => 'open',
            'component' => 'Backend',
            'labels' => ['refactor'],
        ]);

        // Component filter
        $component = Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->set('filterComponents', ['Mobile']);

        $taskIds = collect($component->viewData('columns'))
            ->flatMap(fn ($col) => $col->tasks->pluck('id'));

        $this->assertTrue($taskIds->contains($mobileTask->id));
        $this->assertFalse($taskIds->contains($backendTask->id));

        // Label filter
        $component = Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->set('filterLabel', 'refactor');

        $taskIds = collect($component->viewData('columns'))
            ->flatMap(fn ($col) => $col->tasks->pluck('id'));

        $this->assertFalse($taskIds->contains($mobileTask->id));
        $this->assertTrue($taskIds->contains($backendTask->id));
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

    private function createTemplate(?int $projectId, ?string $type, array $items): ChecklistTemplate
    {
        $template = ChecklistTemplate::create([
            'project_id' => $projectId,
            'type' => $type,
            'name' => 'Template '.fake()->unique()->word(),
        ]);

        foreach (array_values($items) as $index => $label) {
            $template->items()->create([
                'label' => $label,
                'sort_order' => $index,
            ]);
        }

        return $template;
    }
}
