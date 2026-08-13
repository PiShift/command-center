<?php

namespace App\Livewire;

use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Services\ChecklistTemplateService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ProjectBacklog extends Component
{
    public Project $project;

    // UI state
    public bool $showPromoted = false;
    public bool $showAddForm  = false;

    // Add item form
    public string $addTitle       = '';
    public string $addDescription = '';
    public string $addGuide       = '';
    public ?int   $addSprintId    = null;

    // Inline edit
    public ?int   $editingItem      = null;
    public string $editTitle        = '';
    public string $editDescription  = '';
    public string $editGuide        = '';
    public ?int   $editSprintId     = null;

    // Inline promote
    public ?int   $promotingItem      = null;
    public string $promoteTitle       = '';
    public string $promoteDescription = '';
    public string $promoteType        = 'feature';
    public string $promotePriority    = 'medium';
    public ?int   $promoteSprintId    = null;
    public int    $promoteWeight      = 3;
    public ?int   $promoteAssignedTo  = null;
    public string $promoteDueDate     = '';

    // Bulk selection
    public array $selectedItems = [];

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    // ── Add item ────────────────────────────────────────────────────────────

    public function createItem(): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $this->validate([
            'addTitle'       => 'required|string|max:255',
            'addDescription' => 'nullable|string',
            'addGuide'       => 'nullable|string',
            'addSprintId'    => 'nullable|exists:sprints,id',
        ]);

        if ($this->addSprintId) {
            abort_unless(
                $this->project->sprints()->where('id', $this->addSprintId)->exists(),
                422
            );
        }

        $status = (!empty($this->addGuide) || !empty($this->addDescription)) ? 'refined' : 'raw';

        $this->project->backlogItems()->create([
            'title'       => $this->addTitle,
            'description' => $this->addDescription ?: null,
            'guide'       => $this->addGuide ?: null,
            'sprint_id'   => $this->addSprintId,
            'sort_order'  => $this->project->backlogItems()->max('sort_order') + 1,
            'status'      => $status,
            'promoted'    => false,
        ]);

        $this->addTitle       = '';
        $this->addDescription = '';
        $this->addGuide       = '';
        $this->addSprintId    = null;
        $this->showAddForm    = false;

        session()->flash('success', 'Backlog item added.');
    }

    // ── Edit item ────────────────────────────────────────────────────────────

    public function editItem(int $itemId): void
    {
        $item = BacklogItem::where('project_id', $this->project->id)->findOrFail($itemId);
        $this->editingItem     = $itemId;
        $this->editTitle       = $item->title;
        $this->editDescription = $item->description ?? '';
        $this->editGuide       = $item->guide ?? '';
        $this->editSprintId    = $item->sprint_id;
        $this->promotingItem   = null;
    }

    public function saveItem(): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $this->validate([
            'editTitle'       => 'required|string|max:255',
            'editDescription' => 'nullable|string',
            'editGuide'       => 'nullable|string',
            'editSprintId'    => 'nullable|exists:sprints,id',
        ]);

        if ($this->editSprintId) {
            abort_unless(
                $this->project->sprints()->where('id', $this->editSprintId)->exists(),
                422
            );
        }

        $item = BacklogItem::where('project_id', $this->project->id)->findOrFail($this->editingItem);

        $data = [
            'title'       => $this->editTitle,
            'description' => $this->editDescription ?: null,
            'guide'       => $this->editGuide ?: null,
            'sprint_id'   => $this->editSprintId,
        ];

        if (!empty($this->editGuide) || !empty($this->editDescription)) {
            $data['status'] = 'refined';
        }

        $item->update($data);
        $this->editingItem = null;

        session()->flash('success', 'Backlog item updated.');
    }

    public function cancelEdit(): void
    {
        $this->editingItem = null;
    }

    // ── Delete item ──────────────────────────────────────────────────────────

    public function deleteItem(int $itemId): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        $item = BacklogItem::where('project_id', $this->project->id)->findOrFail($itemId);
        $item->delete();
        $this->selectedItems = array_values(array_filter($this->selectedItems, fn ($id) => $id !== $itemId));

        session()->flash('success', 'Backlog item deleted.');
    }

    // ── Promote item ─────────────────────────────────────────────────────────

    public function openPromote(int $itemId): void
    {
        $item = BacklogItem::where('project_id', $this->project->id)->findOrFail($itemId);
        $this->promotingItem      = $itemId;
        $this->promoteTitle       = $item->title;
        $this->promoteDescription = $item->description ?? '';
        $this->promoteType        = 'feature';
        $this->promotePriority    = 'medium';
        $this->promoteSprintId    = $item->sprint_id;
        $this->promoteWeight      = 3;
        $this->promoteAssignedTo  = null;
        $this->promoteDueDate     = '';
        $this->editingItem        = null;
    }

    public function promoteItem(): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $item = BacklogItem::where('project_id', $this->project->id)
            ->where('promoted', false)
            ->findOrFail($this->promotingItem);

        $this->validate([
            'promoteTitle'       => 'required|string|max:255',
            'promoteDescription' => 'nullable|string',
            'promoteType'        => 'required|in:bug,feature,change',
            'promotePriority'    => 'required|in:low,medium,high',
            'promoteSprintId'    => 'nullable|exists:sprints,id',
            'promoteWeight'      => 'required|integer|between:1,5',
            'promoteAssignedTo'  => 'nullable|exists:users,id',
            'promoteDueDate'     => 'nullable|date',
        ]);

        if ($this->promoteSprintId) {
            abort_unless(
                $this->project->sprints()->where('id', $this->promoteSprintId)->exists(),
                422
            );
        }

        $task = Task::create([
            'project_id'  => $this->project->id,
            'sprint_id'   => $this->promoteSprintId,
            'assigned_to' => $this->promoteAssignedTo,
            'title'       => $this->promoteTitle,
            'description' => $this->promoteDescription ?: null,
            'type'        => $this->promoteType,
            'priority'    => $this->promotePriority,
            'status'      => 'open',
            'weight'      => $this->promoteWeight,
            'due_date'    => $this->promoteDueDate ?: null,
            'source'      => 'manual',
        ]);

        app(ChecklistTemplateService::class)->applyToTask($task);

        $item->update([
            'promoted'         => true,
            'promoted_task_id' => $task->id,
            'promoted_at'      => now(),
            'sprint_id'        => $this->promoteSprintId ?? $item->sprint_id,
        ]);

        $this->promotingItem = null;
        session()->flash('success', 'Backlog item promoted to task successfully.');
    }

    public function cancelPromote(): void
    {
        $this->promotingItem = null;
    }

    // ── Bulk selection ───────────────────────────────────────────────────────

    public function toggleItem(int $itemId): void
    {
        if (in_array($itemId, $this->selectedItems)) {
            $this->selectedItems = array_values(
                array_filter($this->selectedItems, fn ($id) => $id !== $itemId)
            );
        } else {
            $this->selectedItems[] = $itemId;
        }
    }

    public function selectAll(): void
    {
        $ids = $this->project->backlogItems()->where('promoted', false)->pluck('id')->toArray();
        $this->selectedItems = $ids;
    }

    public function clearSelection(): void
    {
        $this->selectedItems = [];
    }

    public function reorderBacklogItemInGroup(int $itemId, int $targetItemId): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        if ($itemId === $targetItemId) {
            return;
        }

        $item = BacklogItem::query()
            ->where('project_id', $this->project->id)
            ->where('promoted', false)
            ->findOrFail($itemId);

        $target = BacklogItem::query()
            ->where('project_id', $this->project->id)
            ->where('promoted', false)
            ->findOrFail($targetItemId);

        if ((int) ($item->sprint_id ?? 0) !== (int) ($target->sprint_id ?? 0)) {
            return;
        }

        $items = BacklogItem::query()
            ->where('project_id', $this->project->id)
            ->where('promoted', false)
            ->where(function ($query) use ($item): void {
                if ($item->sprint_id === null) {
                    $query->whereNull('sprint_id');
                } else {
                    $query->where('sprint_id', $item->sprint_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $items = array_values(array_filter($items, fn (int $id): bool => $id !== $item->id));
        $targetIndex = array_search($target->id, $items, true);

        if ($targetIndex === false) {
            $items[] = $item->id;
        } else {
            array_splice($items, $targetIndex, 0, [$item->id]);
        }

        foreach ($items as $index => $id) {
            BacklogItem::query()->whereKey($id)->update(['sort_order' => $index + 1]);
        }
    }

    // ── Bulk actions ─────────────────────────────────────────────────────────

    public function bulkAssignSprint(int $sprintId): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        if (empty($this->selectedItems)) {
            return;
        }

        if ($sprintId > 0) {
            abort_unless(
                $this->project->sprints()->where('id', $sprintId)->exists(),
                422
            );
        }

        BacklogItem::whereIn('id', $this->selectedItems)
            ->where('project_id', $this->project->id)
            ->where('promoted', false)
            ->update(['sprint_id' => $sprintId ?: null]);

        $this->selectedItems = [];
        session()->flash('success', 'Sprint assigned to selected items.');
    }

    public function bulkPromote(): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        if (empty($this->selectedItems)) {
            return;
        }

        $items = BacklogItem::whereIn('id', $this->selectedItems)
            ->where('project_id', $this->project->id)
            ->where('promoted', false)
            ->get();

        foreach ($items as $item) {
            $task = Task::create([
                'project_id'  => $this->project->id,
                'sprint_id'   => $item->sprint_id,
                'title'       => $item->title,
                'description' => $item->description,
                'type'        => 'feature',
                'priority'    => 'medium',
                'status'      => 'open',
                'weight'      => 3,
                'source'      => 'manual',
            ]);

            $item->update([
                'promoted'         => true,
                'promoted_task_id' => $task->id,
                'promoted_at'      => now(),
            ]);
        }

        $this->selectedItems = [];
        session()->flash('success', count($items) . ' item(s) promoted to tasks.');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        if (empty($this->selectedItems)) {
            return;
        }

        $deleted = BacklogItem::whereIn('id', $this->selectedItems)
            ->where('project_id', $this->project->id)
            ->where('promoted', false)
            ->delete();

        $this->selectedItems = [];
        session()->flash('success', $deleted . ' item(s) deleted.');
    }

    public function render()
    {
        $sprints = $this->project->sprints()->orderBy('sort_order')->get(['id', 'name', 'status']);

        $pendingItems = $this->project->backlogItems()
            ->where('promoted', false)
            ->orderBy('sort_order')
            ->with(['sprint'])
            ->get();

        $promotedItems = $this->showPromoted
            ? $this->project->backlogItems()
                ->where('promoted', true)
                ->orderByDesc('promoted_at')
                ->with(['sprint', 'promotedTask'])
                ->get()
            : collect();

        $promotedCount = $this->project->backlogItems()->where('promoted', true)->count();

        $users = User::orderBy('name')->get(['id', 'name', 'color', 'initials']);

        $canManage = auth()->user()->can('manage', $this->project);

        // Group pending items by sprint
        $grouped = $pendingItems->groupBy(fn ($item) => $item->sprint_id ?? 0);

        return view('livewire.project-backlog', compact(
            'sprints', 'pendingItems', 'promotedItems', 'promotedCount',
            'users', 'canManage', 'grouped'
        ));
    }
}
