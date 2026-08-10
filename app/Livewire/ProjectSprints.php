<?php

namespace App\Livewire;

use App\Exceptions\InvalidTaskTransition;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Services\ChecklistTemplateService;
use App\Services\TaskStatusService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProjectSprints extends Component
{
    public Project $project;

    public array $expandedSprints = [];

    // Sprint add form
    public bool $showAddSprint = false;
    public string $addSprintName = '';
    public string $addSprintDescription = '';
    public string $addSprintDeadline = '';

    // Sprint inline edit
    public ?int $editingSprint = null;
    public string $editSprintName = '';
    public string $editSprintDescription = '';
    public string $editSprintDeadline = '';

    // Task inline edit
    public ?int $editingTask = null;
    public string $editTaskTitle = '';
    public string $editTaskStatus = '';
    public string $editTaskPriority = '';
    public ?int $editTaskAssignedTo = null;
    public string $editTaskDueDate = '';
    public int $editTaskWeight = 3;
    public ?int $editTaskSprintId = null;

    // Task bulk move
    public array $selectedTaskIds = [];
    public array $bulkMoveTargetBySprint = [];

    // Task quick-add
    public ?int $addTaskSprintId = null;
    public string $addTaskTitle = '';
    public int $addTaskWeight = 3;
    public string $addTaskType = 'feature';
    public string $addTaskPriority = 'medium';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->expandedSprints = $project->sprints()
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();
    }

    public function toggleSprint(int $sprintId): void
    {
        if (in_array($sprintId, $this->expandedSprints)) {
            $this->expandedSprints = array_values(
                array_filter($this->expandedSprints, fn ($id) => $id !== $sprintId)
            );
        } else {
            $this->expandedSprints[] = $sprintId;
        }
    }

    public function createSprint(): void
    {
        Gate::authorize('manage', $this->project);

        $this->validate([
            'addSprintName'        => 'required|string|max:255',
            'addSprintDescription' => 'nullable|string',
            'addSprintDeadline'    => 'nullable|date',
        ]);

        $sprint = $this->project->sprints()->create([
            'name'        => $this->addSprintName,
            'description' => $this->addSprintDescription ?: null,
            'deadline'    => $this->addSprintDeadline ?: null,
            'sort_order'  => $this->project->sprints()->max('sort_order') + 1,
            'status'      => 'draft',
        ]);

        $this->addSprintName        = '';
        $this->addSprintDescription = '';
        $this->addSprintDeadline    = '';
        $this->showAddSprint        = false;
        $this->expandedSprints[]    = $sprint->id;

        session()->flash('success', 'Sprint created.');
    }

    public function editSprint(int $sprintId): void
    {
        $sprint = Sprint::where('project_id', $this->project->id)->findOrFail($sprintId);
        $this->editingSprint        = $sprintId;
        $this->editSprintName       = $sprint->name;
        $this->editSprintDescription = $sprint->description ?? '';
        $this->editSprintDeadline   = $sprint->deadline?->format('Y-m-d') ?? '';
    }

    public function saveSprint(): void
    {
        Gate::authorize('manage', $this->project);

        $this->validate([
            'editSprintName'        => 'required|string|max:255',
            'editSprintDescription' => 'nullable|string',
            'editSprintDeadline'    => 'nullable|date',
        ]);

        $sprint = Sprint::where('project_id', $this->project->id)->findOrFail($this->editingSprint);
        $sprint->update([
            'name'        => $this->editSprintName,
            'description' => $this->editSprintDescription ?: null,
            'deadline'    => $this->editSprintDeadline ?: null,
        ]);

        $this->editingSprint = null;
        session()->flash('success', 'Sprint updated.');
    }

    public function cancelEditSprint(): void
    {
        $this->editingSprint = null;
    }

    public function deleteSprint(int $sprintId): void
    {
        Gate::authorize('manage', $this->project);
        $sprint = Sprint::where('project_id', $this->project->id)->findOrFail($sprintId);
        $sprint->delete();
        $this->expandedSprints = array_values(
            array_filter($this->expandedSprints, fn ($id) => $id !== $sprintId)
        );
        session()->flash('success', 'Sprint deleted.');
    }

    public function editTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        $this->editingTask        = $taskId;
        $this->editTaskTitle      = $task->title;
        $this->editTaskStatus     = $task->status;
        $this->editTaskPriority   = $task->priority;
        $this->editTaskAssignedTo = $task->assigned_to;
        $this->editTaskDueDate    = $task->due_date?->format('Y-m-d') ?? '';
        $this->editTaskWeight     = $task->weight ?? 3;
        $this->editTaskSprintId   = $task->sprint_id;
    }

    public function saveTask(): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($this->editingTask);

        $this->validate([
            'editTaskTitle'      => 'required|string|max:255',
            'editTaskStatus'     => 'required|string',
            'editTaskPriority'   => 'required|in:low,medium,high',
            'editTaskAssignedTo' => 'nullable|exists:users,id',
            'editTaskDueDate'    => 'nullable|date',
            'editTaskWeight'     => 'required|integer|between:1,5',
            'editTaskSprintId'   => [
                'nullable',
                'integer',
                Rule::exists('sprints', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
            ],
        ]);

        $oldStatus = $task->status;

        // Status changes go through the central workflow service; the rest of
        // the fields update directly.
        $statusRequested = $this->editTaskStatus !== $oldStatus;
        if ($statusRequested) {
            try {
                app(TaskStatusService::class)->transition($task, $this->editTaskStatus);
            } catch (InvalidTaskTransition $e) {
                $this->addError('editTaskStatus', $e->getMessage());

                return;
            }
        }

        $task->update([
            'title'       => $this->editTaskTitle,
            'priority'    => $this->editTaskPriority,
            'assigned_to' => $this->editTaskAssignedTo,
            'due_date'    => $this->editTaskDueDate ?: null,
            'weight'      => $this->editTaskWeight,
            'sprint_id'   => $this->editTaskSprintId ?: null,
        ]);

        $this->editingTask = null;
        $this->dispatch('task-updated');
    }

    public function cancelEdit(): void
    {
        $this->editingTask = null;
        $this->editTaskSprintId = null;
    }

    public function bulkMoveSelectedTasks(int $sourceSprintId): void
    {
        Gate::authorize('manage', $this->project);

        $selectedIds = collect($this->selectedTaskIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Select at least one task to move.');

            return;
        }

        $targetSprintIdRaw = $this->bulkMoveTargetBySprint[$sourceSprintId] ?? null;
        $targetSprintId = $targetSprintIdRaw !== null && $targetSprintIdRaw !== '' ? (int) $targetSprintIdRaw : null;

        if ($targetSprintId !== null) {
            $this->validate([
                "bulkMoveTargetBySprint.$sourceSprintId" => [
                    'required',
                    'integer',
                    Rule::exists('sprints', 'id')->where(fn ($query) => $query->where('project_id', $this->project->id)),
                ],
            ]);
        }

        $query = Task::query()
            ->where('project_id', $this->project->id)
            ->whereIn('id', $selectedIds)
            ->where('sprint_id', $sourceSprintId);

        $movedCount = (int) $query->update([
            'sprint_id' => $targetSprintId,
        ]);

        $this->selectedTaskIds = [];
        $this->bulkMoveTargetBySprint = [];

        if ($movedCount > 0) {
            session()->flash('success', "$movedCount task(s) moved.");
        } else {
            session()->flash('error', 'No tasks were moved.');
        }
    }

    public function openTask(int $taskId): void
    {
        $this->dispatch('open-task', id: $taskId);
    }

    public function showAddTask(int $sprintId): void
    {
        $this->addTaskSprintId = $sprintId;
        $this->addTaskTitle    = '';
        $this->addTaskWeight   = 3;
        $this->addTaskType     = 'feature';
        $this->addTaskPriority = 'medium';
    }

    public function cancelAddTask(): void
    {
        $this->addTaskSprintId = null;
    }

    public function createTask(): void
    {
        Gate::authorize('manage', $this->project);

        $this->validate([
            'addTaskTitle'    => 'required|string|max:255',
            'addTaskWeight'   => 'required|integer|between:1,5',
            'addTaskType'     => 'required|in:bug,feature,change',
            'addTaskPriority' => 'required|in:low,medium,high',
        ]);

        $task = Task::create([
            'project_id' => $this->project->id,
            'sprint_id'  => $this->addTaskSprintId,
            'title'      => $this->addTaskTitle,
            'type'       => $this->addTaskType,
            'priority'   => $this->addTaskPriority,
            'status'     => 'open',
            'weight'     => $this->addTaskWeight,
            'source'     => 'manual',
        ]);

        app(ChecklistTemplateService::class)->applyToTask($task);

        if (!in_array($this->addTaskSprintId, $this->expandedSprints)) {
            $this->expandedSprints[] = $this->addTaskSprintId;
        }

        $this->addTaskSprintId = null;
        $this->addTaskTitle    = '';
    }

    public function render()
    {
        $canManage = auth()->user()->can('manage', $this->project);

        $query = $this->project->sprints()
            ->orderBy('sort_order')
            ->with([
                'tasks' => function ($q) {
                    $q->with(['assignee', 'checklists'])->withCount('comments');
                },
            ]);

        if (!$canManage) {
            $query->whereIn('status', ['active', 'completed']);
        }

        $sprints = $query->get();
        $users   = User::orderBy('name')->get(['id', 'name', 'color', 'initials']);
        $columns = KanbanColumn::orderBy('position')->get(['slug', 'name']);

        $allProjectSprints = $this->project->sprints()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'status']);

        return view('livewire.project-sprints', compact('sprints', 'canManage', 'users', 'columns', 'allProjectSprints'));
    }
}
