<?php

namespace App\Livewire;

use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class TaskModal extends Component
{
    public bool $open = false;

    // Task data
    public ?int $taskId = null;
    public string $title = '';
    public string $description = '';
    public string $guide = '';
    public string $status = 'todo';
    public string $priority = 'medium';
    public ?int $projectId = null;
    public ?int $assignedTo = null;
    public string $dueDate = '';
    public string $estimatedHours = '';

    // New task mode
    public bool $isNew = false;

    // Comment
    public string $commentBody = '';

    // Checklist
    public string $newChecklistLabel = '';

    // Edit inline
    public bool $editingTitle = false;
    public bool $editingDescription = false;

    protected function rules(): array
    {
        return [
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'status'         => 'required|string',
            'priority'       => 'required|string|in:critical,high,medium,low',
            'projectId'      => 'nullable|exists:projects,id',
            'assignedTo'     => 'nullable|exists:users,id',
            'dueDate'        => 'nullable|date',
            'estimatedHours' => 'nullable|numeric|min:0|max:999',
        ];
    }

    public function getListeners(): array
    {
        return [
            'open-task'  => 'openTask',
            'new-task'   => 'newTask',
        ];
    }

    public function openTask(int $id): void
    {
        $task = Task::with(['project', 'assignee', 'comments.author', 'comments.media', 'media'])->findOrFail($id);

        $this->taskId          = $task->id;
        $this->title           = $task->title;
        $this->description     = $task->description ?? '';
        $this->guide           = $task->guide ?? '';
        $this->status          = $task->status;
        $this->priority        = $task->priority;
        $this->projectId       = $task->project_id;
        $this->assignedTo      = $task->assigned_to;
        $this->dueDate         = $task->due_date?->format('Y-m-d') ?? '';
        $this->estimatedHours  = $task->estimated_hours ?? '';
        $this->commentBody     = '';
        $this->newChecklistLabel = '';
        $this->editingTitle    = false;
        $this->editingDescription = false;
        $this->isNew           = false;
        $this->open            = true;
    }

    public function newTask(?int $projectId = null): void
    {
        $this->taskId          = null;
        $this->title           = '';
        $this->description     = '';
        $this->guide           = '';
        $this->status          = 'todo';
        $this->priority        = 'medium';
        $this->projectId       = $projectId;
        $this->assignedTo      = null;
        $this->dueDate         = '';
        $this->estimatedHours  = '';
        $this->commentBody     = '';
        $this->editingTitle    = true;
        $this->editingDescription = false;
        $this->isNew           = true;
        $this->open            = true;
    }

    public function saveField(string $field): void
    {
        if (! $this->taskId) return;
        $this->validateOnly($field);

        $task = Task::findOrFail($this->taskId);

        $fieldToAbility = [
            'title'          => 'editMeta',
            'description'    => 'editMeta',
            'status'         => 'editStatus',
            'priority'       => 'editPriority',
            'projectId'      => 'editProject',
            'assignedTo'     => 'editAssignee',
            'dueDate'        => 'editDates',
            'estimatedHours' => 'editDates',
        ];

        Gate::authorize($fieldToAbility[$field] ?? 'editMeta', $task);

        $map = [
            'title'          => 'title',
            'description'    => 'description',
            'status'         => 'status',
            'priority'       => 'priority',
            'projectId'      => 'project_id',
            'assignedTo'     => 'assigned_to',
            'dueDate'        => 'due_date',
            'estimatedHours' => 'estimated_hours',
        ];
        $task->update([$map[$field] => $this->$field ?: null]);
        $this->editingTitle = false;
        $this->editingDescription = false;
    }

    public function saveNew(): void
    {
        $this->validate();
        $task = Task::create([
            'title'           => $this->title,
            'description'     => $this->description ?: null,
            'status'          => $this->status,
            'priority'        => $this->priority,
            'project_id'      => $this->projectId,
            'assigned_to'     => $this->assignedTo,
            'due_date'        => $this->dueDate ?: null,
            'estimated_hours' => $this->estimatedHours ?: null,
        ]);
        $this->taskId = $task->id;
        $this->isNew  = false;
        $this->dispatch('task-saved');
        $this->openTask($task->id);
    }

    public function addComment(): void
    {
        $this->validate(['commentBody' => 'required|string|max:2000']);
        TaskComment::create([
            'task_id' => $this->taskId,
            'user_id' => auth()->id(),
            'body'    => $this->commentBody,
        ]);
        $this->commentBody = '';
        // Refresh comments via re-render
    }

    public function deleteComment(int $id): void
    {
        $comment = TaskComment::findOrFail($id);
        $task    = Task::findOrFail($comment->task_id);
        Gate::authorize('deleteComment', $task);
        $comment->delete();
    }

    // ── Checklist ──────────────────────────────────────────────────────────────

    public function addChecklistItem(string $label): void
    {
        if (! $this->taskId || ! trim($label)) return;
        $task = Task::findOrFail($this->taskId);
        $max  = $task->checklists()->max('sort_order') ?? -1;
        TaskChecklist::create([
            'task_id'    => $this->taskId,
            'label'      => trim($label),
            'is_checked' => false,
            'sort_order' => $max + 1,
        ]);
        $this->newChecklistLabel = '';
    }

    public function toggleChecklistItem(int $id): void
    {
        $item = TaskChecklist::where('task_id', $this->taskId)->findOrFail($id);
        $item->update(['is_checked' => ! $item->is_checked]);
    }

    public function deleteChecklistItem(int $id): void
    {
        TaskChecklist::where('task_id', $this->taskId)->findOrFail($id)->delete();
    }

    public function renameChecklistItem(int $id, string $label): void
    {
        if (! trim($label)) return;
        TaskChecklist::where('task_id', $this->taskId)->findOrFail($id)->update(['label' => trim($label)]);
    }

    public function saveGuide(): void    {
        if (! $this->taskId) return;
        $task = Task::findOrFail($this->taskId);
        Gate::authorize('editMeta', $task);
        $this->validate(['guide' => 'nullable|string|max:65535']);
        $task->update(['guide' => $this->guide ?: null]);
        $this->dispatch('guide-saved');
    }

    public function claimTask(): void    {
        if (! $this->taskId) return;
        $task = Task::findOrFail($this->taskId);
        Gate::authorize('claim', $task);

        if ($task->assigned_to !== null) {
            session()->flash('error', 'This task is already assigned to someone.');
            return;
        }

        $user = auth()->user();
        $task->assigned_to = $user->id;
        $task->status      = 'todo';
        $task->save();

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->log("Task claimed by {$user->name} — status changed to todo");

        $this->open = false;
        session()->flash('success', 'Task claimed! It now appears in your tasks.');
        $this->dispatch('task-claimed');
    }

    public function deleteAttachment(int $mediaId): void
    {
        if (! $this->taskId) return;
        $task  = Task::findOrFail($this->taskId);
        $user  = auth()->user();
        $media = $task->media()->findOrFail($mediaId);
        abort_unless(
            ($media->custom_properties['uploaded_by'] ?? null) === $user->id
                || $user->hasPermission('tasks.edit_any'),
            403
        );
        $media->delete();
    }

    // ───────────────────────────────────────────────────────────────────────────

    public function close(): void
    {
        $this->open = false;
        $this->dispatch('task-modal-closed');
    }

    public function render()
    {
        $task = $this->taskId
            ? Task::with(['project', 'assignee', 'comments.author', 'comments.media', 'checklists', 'media'])->find($this->taskId)
            : null;

        $canEdit = [
            'meta'          => ! $task || Gate::allows('editMeta', $task),
            'status'        => ! $task || Gate::allows('editStatus', $task),
            'priority'      => ! $task || Gate::allows('editPriority', $task),
            'project'       => ! $task || Gate::allows('editProject', $task),
            'assignee'      => ! $task || Gate::allows('editAssignee', $task),
            'dates'         => ! $task || Gate::allows('editDates', $task),
            'deleteComment' => $task && Gate::allows('deleteComment', $task),
            'attachments'   => $task && (auth()->user()->hasPermission('tasks.edit_own') || auth()->user()->hasPermission('tasks.edit_any')),
        ];

        $canClaim = $task && Gate::allows('claim', $task);

        $projects = Project::orderBy('name')->get(['id', 'name', 'color']);
        $users    = User::orderBy('name')->get(['id', 'name', 'color', 'initials']);
        $columns  = KanbanColumn::orderBy('position')->get(['slug', 'name', 'color']);

        return view('livewire.task-modal', compact('task', 'projects', 'users', 'columns', 'canEdit', 'canClaim'));
    }
}
