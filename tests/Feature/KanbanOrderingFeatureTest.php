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

    public function test_task_moved_to_todo_lands_at_top_without_shuffling_remaining_open_tasks(): void
    {
        $manager = $this->createUserWithRole('manager');
        [, $project] = $this->createCustomerAndProject();

        KanbanColumn::query()->updateOrCreate(['slug' => 'todo'], ['name' => 'To Do', 'position' => 1]);
        KanbanColumn::query()->updateOrCreate(['slug' => 'done'], ['name' => 'Done', 'position' => 2]);

        $first = $this->createTask($project, ['title' => 'First', 'status' => 'open']);
        $second = $this->createTask($project, ['title' => 'Second', 'status' => 'open']);
        $third = $this->createTask($project, ['title' => 'Third', 'status' => 'open']);

        // Establish known starting order: First, Second, Third
        $first->update(['kanban_position' => 1]);
        $second->update(['kanban_position' => 2]);
        $third->update(['kanban_position' => 3]);

        Livewire::actingAs($manager)
            ->test(KanbanBoard::class)
            ->call('moveTask', $second->id, 'todo');

        $todoTop = Task::query()
            ->where('status', 'todo')
            ->orderBy('kanban_position')
            ->firstOrFail();

        $this->assertSame($second->id, $todoTop->id, 'Moved task should land at top of destination column.');

        $openIds = Task::query()
            ->where('status', 'open')
            ->orderBy('kanban_position')
            ->pluck('id')
            ->all();

        $this->assertSame([$first->id, $third->id], $openIds, 'Remaining open tasks should keep their relative order.');
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
