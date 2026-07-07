<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Livewire\KanbanBoard;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskInvoiceOverride;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskBillingFeatureTest extends TestCase
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

    public function test_task_linked_invoice_rows_persist_real_task_links_and_preserve_edits(): void
    {
        $manager = $this->createUserWithRole('manager');
        [$customer, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, [
            'title' => 'Billable task',
            'estimated_hours' => 3,
        ]);

        $response = $this->actingAs($manager)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'currency' => 'MRU',
            'items' => [[
                'task_id' => $task->id,
                'description' => 'Custom billing description',
                'quantity' => 2,
                'unit' => 'hours',
                'unit_price' => 150,
                'discount_type' => 'percent',
                'discount_value' => 10,
                'cost_price' => 75,
            ]],
        ]);

        $invoice = Invoice::query()->firstOrFail();
        $item = $invoice->items()->firstOrFail();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('task', $item->type);
        $this->assertSame($task->id, $item->task_id);
        $this->assertSame('Custom billing description', $item->description);
        $this->assertSame(2.0, $item->quantity);
        $this->assertSame('hours', $item->unit);
        $this->assertSame(150.0, $item->unit_price);
        $this->assertSame(10.0, $item->discount_value);
        $this->assertSame(75.0, $item->cost_price);
    }

    public function test_manual_invoice_rows_remain_manual(): void
    {
        $manager = $this->createUserWithRole('manager');
        [$customer, $project] = $this->createCustomerAndProject();

        $this->actingAs($manager)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'currency' => 'MRU',
            'items' => [[
                'description' => 'Historical retainer',
                'quantity' => 1,
                'unit' => 'fixed',
                'unit_price' => 500,
            ]],
        ])->assertRedirect();

        $item = InvoiceItem::query()->firstOrFail();

        $this->assertSame('manual', $item->type);
        $this->assertNull($item->task_id);
        $this->assertSame('Historical retainer', $item->description);
    }

    public function test_task_invoice_status_is_invoiced_for_active_unpaid_link_and_visible_only_to_billing_roles(): void
    {
        $manager = $this->createUserWithRole('manager');
        $developer = $this->createUserWithRole('developer');
        [$customer, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project);
        $invoice = $this->createInvoice($customer, $project);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'task',
            'task_id' => $task->id,
            'description' => $task->title,
            'quantity' => 1,
            'unit' => 'hours',
            'unit_price' => 100,
            'sort_order' => 0,
        ]);

        $task->load(['activeInvoiceItem.invoice', 'invoiceOverride']);

        $this->assertSame('invoiced', $task->invoiceStatus);

        $this->actingAs($manager)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Invoiced');

        $this->actingAs($developer)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertDontSee('Invoiced')
            ->assertDontSee('Manual Billing Override')
            ->assertDontSee('invoice_status');
    }

    public function test_cancelling_an_invoice_makes_the_task_invoiceable_again(): void
    {
        $manager = $this->createUserWithRole('manager');
        [$customer, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project);
        $invoice = $this->createInvoice($customer, $project);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'task',
            'task_id' => $task->id,
            'description' => $task->title,
            'quantity' => 1,
            'unit' => 'hours',
            'unit_price' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($manager)
            ->patch(route('invoices.cancel', $invoice))
            ->assertRedirect();

        $this->assertSame(
            'not_invoiced',
            $task->fresh()->load(['activeInvoiceItem.invoice', 'invoiceOverride'])->invoiceStatus,
        );
    }

    public function test_paid_invoice_marks_linked_task_as_paid(): void
    {
        [$customer, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project);
        $invoice = $this->createInvoice($customer, $project, [
            'payment_status' => 'paid',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'task',
            'task_id' => $task->id,
            'description' => $task->title,
            'quantity' => 1,
            'unit' => 'hours',
            'unit_price' => 100,
            'sort_order' => 0,
        ]);

        $this->assertSame(
            'paid',
            $task->fresh()->load(['activeInvoiceItem.invoice', 'invoiceOverride'])->invoiceStatus,
        );
    }

    public function test_invoice_create_view_includes_billing_status_data_for_picker_filtering(): void
    {
        $manager = $this->createUserWithRole('manager');
        [$customer, $project] = $this->createCustomerAndProject();
        $readyTask = $this->createTask($project, ['title' => 'Ready task']);
        $linkedTask = $this->createTask($project, ['title' => 'Already invoiced task']);
        $overrideTask = $this->createTask($project, ['title' => 'Override task']);
        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => 'active',
        ]);

        $readyTask->update(['sprint_id' => $sprint->id]);
        $linkedTask->update(['sprint_id' => $sprint->id]);
        $overrideTask->update(['sprint_id' => $sprint->id]);

        $invoice = $this->createInvoice($customer, $project);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'task',
            'task_id' => $linkedTask->id,
            'description' => $linkedTask->title,
            'quantity' => 1,
            'unit' => 'hours',
            'unit_price' => 100,
            'sort_order' => 0,
        ]);

        TaskInvoiceOverride::create([
            'task_id' => $overrideTask->id,
            'status' => 'paid',
            'marked_by' => $manager->id,
            'marked_at' => now(),
        ]);

        $response = $this->actingAs($manager)->get(route('invoices.create'));

        $response->assertOk()
            ->assertSee('Show already-invoiced tasks')
            ->assertSee('"invoice_status":"not_invoiced"', false)
            ->assertSee('"invoice_status":"invoiced"', false)
            ->assertSee('"invoice_status":"paid"', false);
    }

    public function test_manual_billing_override_is_logged_updates_precedence_and_can_be_removed(): void
    {
        $manager = $this->createUserWithRole('manager');
        [$customer, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project);
        $invoice = $this->createInvoice($customer, $project);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'task',
            'task_id' => $task->id,
            'description' => $task->title,
            'quantity' => 1,
            'unit' => 'hours',
            'unit_price' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($manager)
            ->post(route('task-invoice-overrides.store', $task), [
                'status' => 'paid',
                'note' => 'Historical payment received',
            ])
            ->assertRedirect();

        $override = TaskInvoiceOverride::query()->firstOrFail();

        $this->assertSame($task->id, $override->task_id);
        $this->assertSame('paid', $override->status);
        $this->assertSame('Historical payment received', $override->note);
        $this->assertSame($manager->id, $override->marked_by);
        $this->assertNotNull($override->marked_at);
        $this->assertSame(
            'paid',
            $task->fresh()->load(['activeInvoiceItem.invoice', 'invoiceOverride'])->invoiceStatus,
        );

        $this->actingAs($manager)
            ->post(route('task-invoice-overrides.store', $task), [
                'status' => 'invoiced',
                'note' => 'Adjusted status',
            ])
            ->assertRedirect();

        $this->assertSame('invoiced', $override->fresh()->status);
        $this->assertSame('Adjusted status', $override->fresh()->note);

        $this->actingAs($manager)
            ->delete(route('task-invoice-overrides.destroy', $task))
            ->assertRedirect();

        $this->assertNull(TaskInvoiceOverride::query()->find($override->id));
    }

    public function test_manual_billing_override_requires_manage_permission(): void
    {
        $developer = $this->createUserWithRole('developer');
        [, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project);

        $this->actingAs($developer)
            ->post(route('task-invoice-overrides.store', $task), [
                'status' => 'paid',
            ])
            ->assertForbidden();
    }

    public function test_developer_board_payload_does_not_leak_billing_data(): void
    {
        $developer = $this->createUserWithRole('developer');
        [$customer, $project] = $this->createCustomerAndProject();
        $task = $this->createTask($project, [
            'assigned_to' => $developer->id,
            'title' => 'Developer task',
            'status' => 'todo',
        ]);
        $invoice = $this->createInvoice($customer, $project);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'task',
            'task_id' => $task->id,
            'description' => $task->title,
            'quantity' => 1,
            'unit' => 'hours',
            'unit_price' => 100,
            'sort_order' => 0,
        ]);

        Livewire::actingAs($developer)
            ->test(KanbanBoard::class)
            ->assertSee('Developer task')
            ->assertDontSee('invoice_status')
            ->assertDontSee($invoice->invoice_number)
            ->assertDontSee('Invoiced');
    }

    public function test_project_ready_to_bill_rollup_matches_underlying_task_statuses(): void
    {
        $manager = $this->createUserWithRole('manager');
        [$customer, $project] = $this->createCustomerAndProject();
        $readyTask = $this->createTask($project, ['title' => 'Ready to bill']);
        $linkedTask = $this->createTask($project, ['title' => 'Already linked']);
        $overrideTask = $this->createTask($project, ['title' => 'Override billed']);
        $this->createTask($project, ['title' => 'Still open', 'status' => 'todo']);

        $invoice = $this->createInvoice($customer, $project);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'task',
            'task_id' => $linkedTask->id,
            'description' => $linkedTask->title,
            'quantity' => 1,
            'unit' => 'hours',
            'unit_price' => 100,
            'sort_order' => 0,
        ]);

        TaskInvoiceOverride::create([
            'task_id' => $overrideTask->id,
            'status' => 'invoiced',
            'marked_by' => $manager->id,
            'marked_at' => now(),
        ]);

        $this->assertSame(1, Task::query()->whereBelongsTo($project)->readyToBill()->count());

        $this->actingAs($manager)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Ready to Bill')
            ->assertSee('1 task ready to bill');
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
            'status' => 'done',
            'source' => 'manual',
            'estimated_hours' => 2,
        ], $attributes));
    }

    private function createInvoice(Customer $customer, Project $project, array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'currency' => 'MRU',
            'status' => 'draft',
            'payment_status' => 'unpaid',
        ], $attributes));
    }
}
