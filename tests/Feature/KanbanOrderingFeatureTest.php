<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Livewire\KanbanBoard;
use App\Models\Customer;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KanbanOrderingFeatureTest extends TestCase
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

    public function test_kanban_reorder_updates_project_customer_order_consistently(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();

        KanbanColumn::query()->updateOrCreate(['slug' => 'todo'], ['name' => 'To Do', 'position' => 1]);
        KanbanColumn::query()->updateOrCreate(['slug' => 'done'], ['name' => 'Done', 'position' => 2]);

        $first = $this->createTask($project, ['title' => 'First', 'status' => 'open']);
        $second = $this->createTask($project, ['title' => 'Second', 'status' => 'open']);
        $third = $this->createTask($project, ['title' => 'Third', 'status' => 'open']);

        $first->update(['customer_order' => 1]);
        $second->update(['customer_order' => 2]);
        $third->update(['customer_order' => 3]);

        // Move second task before first task within the same kanban column.
        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('reorderTaskInColumn', $second->id, $first->id);

        $orderedIds = Task::query()
            ->where('project_id', $project->id)
            ->orderBy('customer_order')
            ->pluck('id')
            ->all();

        $this->assertSame([$second->id, $first->id, $third->id], $orderedIds);

        // Moving status should keep the custom order stable.
        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $second->id, 'todo');

        $afterMoveIds = Task::query()
            ->where('project_id', $project->id)
            ->orderBy('customer_order')
            ->pluck('id')
            ->all();

        $this->assertSame([$second->id, $first->id, $third->id], $afterMoveIds);
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
            'status' => 'todo',
            'source' => 'manual',
        ], $attributes));
    }
}
