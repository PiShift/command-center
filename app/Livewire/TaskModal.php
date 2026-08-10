<?php

namespace App\Livewire;

use App\Events\AgentCommentPosted;
use App\Exceptions\InvalidTaskTransition;
use App\Models\Agent;
use App\Models\AgentTaskQueue;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskCommentNotification;
use App\Services\AgentTriggerService;
use App\Services\ChecklistTemplateService;
use App\Services\TaskStatusService;
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
    public string $type = 'feature';
    public ?string $component = null;
    public array $labels = [];
    public ?int $projectId = null;
    public ?int $sprintId = null;
    public ?int $assignedTo = null;
    public ?string $agentId = null;
    public string $dueDate = '';
    public string $estimatedHours = '';

    // New task mode
    public bool $isNew = false;

    // Comment
    public string $commentBody = '';

    // Checklist
    public string $newChecklistLabel = '';

    // Labels
    public string $newLabel = '';

    // Request changes dialog
    public string $changeCategory = '';
    public string $changeExplanation = '';

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
            'type'           => 'required|string|in:bug,feature,change',
            'component'      => 'nullable|string|max:100',
            'labels'         => 'nullable|array|max:20',
            'labels.*'       => 'string|max:50',
            'projectId'      => 'nullable|exists:projects,id',
            'sprintId'       => 'nullable|exists:sprints,id',
            'assignedTo'     => 'nullable|exists:users,id',
            'agentId'        => 'nullable|exists:agents,id',
            'dueDate'        => 'nullable|date',
            'estimatedHours' => 'nullable|numeric|min:0|max:999',
            'changeCategory' => 'nullable|string|in:Incomplete,Doesn\'t match spec,Bug / broken,Unprofessional / careless,Other',
            'changeExplanation' => 'nullable|string|min:3',
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
        $task = Task::with(['project', 'sprint', 'assignee', 'agent', 'comments.author', 'comments.media', 'comments.agent', 'media'])->findOrFail($id);

        $this->taskId          = $task->id;
        $this->title           = $task->title;
        $this->description     = $task->description ?? '';
        $this->guide           = $task->guide ?? '';
        $this->status          = $task->status;
        $this->priority        = $task->priority;
        $this->type            = $task->type ?? 'feature';
        $this->component       = $task->component;
        $this->labels          = array_values($task->labels ?? []);
        $this->projectId       = $task->project_id;
        $this->sprintId        = $task->sprint_id;
        $this->assignedTo      = $task->assigned_to;
        $this->agentId         = $task->agent_id;
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
        $this->type            = 'feature';
        $this->component       = null;
        $this->labels          = [];
        $this->newLabel        = '';
        $this->projectId       = $projectId;
        $this->sprintId        = null;
        $this->assignedTo      = null;
        $this->agentId         = null;
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
            'type'           => 'editMeta',
            'component'      => 'editMeta',
            'projectId'      => 'editProject',
            'sprintId'       => 'editProject',
            'assignedTo'     => 'editAssignee',
            'agentId'        => 'editAssignee',
            'dueDate'        => 'editDates',
            'estimatedHours' => 'editDates',
        ];

        Gate::authorize($fieldToAbility[$field] ?? 'editMeta', $task);

        $map = [
            'title'          => 'title',
            'description'    => 'description',
            'status'         => 'status',
            'priority'       => 'priority',
            'type'           => 'type',
            'component'      => 'component',
            'projectId'      => 'project_id',
            'sprintId'       => 'sprint_id',
            'assignedTo'     => 'assigned_to',
            'agentId'        => 'agent_id',
            'dueDate'        => 'due_date',
            'estimatedHours' => 'estimated_hours',
        ];

        $oldStatus     = $task->status;
        $oldAgentId    = $task->agent_id;

        // Status changes must go through the central workflow service —
        // legality, conditions, validators, and post-functions apply.
        if ($field === 'status') {
            try {
                app(TaskStatusService::class)->transition($task, (string) $this->status);
            } catch (InvalidTaskTransition $e) {
                $this->status = $task->status;
                $this->addError('status', $e->getMessage());

                return;
            }

            $this->editingTitle = false;
            $this->editingDescription = false;

            return;
        }

        $task->update([$map[$field] => $this->$field ?: null]);

        if ($field === 'projectId') {
            // Keep the sprint consistent with the newly selected project
            $sprintBelongsToProject = $this->sprintId
                && Sprint::where('id', $this->sprintId)->where('project_id', $this->projectId)->exists();
            if (! $sprintBelongsToProject) {
                $this->sprintId = null;
                $task->update(['sprint_id' => null]);
            }

            $allowedComponents = Project::find($this->projectId)?->components ?? [];
            if ($this->component && ! in_array($this->component, $allowedComponents, true)) {
                $this->component = null;
                $task->update(['component' => null]);
            }
        }

        if ($field === 'agentId') {
            $task->refresh();
            $this->syncAgentQueue($task, $this->agentId, $oldAgentId);
        }

        $this->editingTitle = false;
        $this->editingDescription = false;
    }

    public function saveNew(): void
    {
        $this->validate();
        $task = Task::create([
            'title'           => $this->title,
            'description'     => $this->description ?: null,
            'type'            => $this->type,
            'component'       => $this->component ?: null,
            'status'          => $this->status,
            'priority'        => $this->priority,
            'project_id'      => $this->projectId,
            'sprint_id'       => $this->sprintId,
            'assigned_to'     => $this->assignedTo,
            'agent_id'        => $this->agentId,
            'due_date'        => $this->dueDate ?: null,
            'estimated_hours' => $this->estimatedHours ?: null,
            'labels'          => $this->labels ?: null,
        ]);
        app(ChecklistTemplateService::class)->applyToTask($task);
        $this->taskId = $task->id;
        $this->isNew  = false;
        $this->syncAgentQueue($task, $this->agentId, null);
        $this->dispatch('task-saved');
        $this->openTask($task->id);
    }

    /**
     * The component dropdown (x-searchable-select) syncs via $wire.set,
     * so persist immediately once the task exists.
     */
    public function updatedComponent($value): void
    {
        if (! $this->taskId) {
            return;
        }

        $this->saveField('component');
    }

    public function addComment(): void
    {
        $this->validate(['commentBody' => 'required|string|max:2000']);

        $comment = TaskComment::create([
            'task_id' => $this->taskId,
            'user_id' => auth()->id(),
            'body'    => $this->commentBody,
        ]);

        $task = Task::with('project.teams')->find($this->taskId);
        if (!$task) {
            $this->commentBody = '';
            return;
        }

        // Broadcast via WebSocket
        $workspaceId = $task?->project?->teams?->first()?->id;
        if ($workspaceId) {
            \App\WebSocket\WebSocketBroadcaster::broadcastCommentCreated($comment, (string) $workspaceId);
        }

        // Broadcast real-time event via Reverb/Echo
        broadcast(new AgentCommentPosted(
            taskId: (int) $task->id,
            body: $comment->body,
            authorName: auth()->user()->name,
        ));

        // Trigger agent if task has agent assigned
        $task->load('agent.runtime');
        AgentTriggerService::triggerOnComment($task, $comment);

        // Send notifications
        $commenter = auth()->user();
        $recipients = collect();

        if ($task->assigned_to && $task->assigned_to !== $commenter->id) {
            $recipients->push($task->assignee);
        }

        $prevCommenters = $task->comments()
            ->where('user_id', '!=', $commenter->id)
            ->where('id', '!=', $comment->id)
            ->pluck('user_id')
            ->unique()
            ->map(fn($id) => User::find($id))
            ->filter();

        $recipients = $recipients->merge($prevCommenters)->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskCommentNotification($task->load('project'), $comment, $commenter));
        }

        $this->commentBody = '';
        // Trigger re-render to show new comment
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
        $item = TaskChecklist::where('task_id', $this->taskId)->findOrFail($id);

        // Template-sourced items are locked: they can be checked off but not deleted.
        if ($item->isLocked()) {
            return;
        }

        $item->delete();
    }

    public function renameChecklistItem(int $id, string $label): void
    {
        if (! trim($label)) return;
        TaskChecklist::where('task_id', $this->taskId)->findOrFail($id)->update(['label' => trim($label)]);
    }

    // ── Labels ─────────────────────────────────────────────────────────────────

    public function addLabel(string $label): void
    {
        $label = trim($label);

        if ($label === '' || mb_strlen($label) > 50) {
            return;
        }

        $exists = collect($this->labels)
            ->contains(fn (string $existing) => mb_strtolower($existing) === mb_strtolower($label));

        if ($exists) {
            $this->newLabel = '';

            return;
        }

        $this->labels[] = $label;
        $this->newLabel = '';
        $this->persistLabels();
    }

    public function removeLabel(int $index): void
    {
        unset($this->labels[$index]);
        $this->labels = array_values($this->labels);
        $this->persistLabels();
    }

    private function persistLabels(): void
    {
        if (! $this->taskId) {
            return;
        }

        $task = Task::findOrFail($this->taskId);
        Gate::authorize('editMeta', $task);
        $task->update(['labels' => $this->labels ?: null]);
    }

    // ── Request changes ───────────────────────────────────────────────────────

    public function requestChanges(): void
    {
        if (! $this->taskId) {
            return;
        }

        $task = Task::findOrFail($this->taskId);
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        $data = $this->validate([
            'changeCategory' => 'required|string|in:Incomplete,Doesn\'t match spec,Bug / broken,Unprofessional / careless,Other',
            'changeExplanation' => 'required|string|min:3',
        ]);

        try {
            app(TaskStatusService::class)->transition(
                $task,
                'changes-requested',
                input: [
                    'category' => $data['changeCategory'],
                    'explanation' => $data['changeExplanation'],
                ],
                postPersist: function (Task $task, ?\App\Models\TaskStatusHistory $history) use ($data): void {
                    \App\Models\TaskChangeRequest::query()->create([
                        'task_id' => $task->id,
                        'task_status_history_id' => $history?->id,
                        'category' => $data['changeCategory'],
                        'explanation' => $data['changeExplanation'],
                    ]);
                },
            );
        } catch (InvalidTaskTransition $e) {
            $this->addError('changeCategory', $e->getMessage());

            return;
        }

        $this->status = $task->fresh()->status;
        $this->changeCategory = '';
        $this->changeExplanation = '';
        $this->dispatch('changes-requested');
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
        $task->save();
        app(TaskStatusService::class)->transition($task, 'todo');

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->log('claimed task');

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

    public function deleteTask(): void
    {
        if (! $this->taskId) {
            return;
        }

        $task = Task::findOrFail($this->taskId);
        Gate::authorize('delete', $task);

        $deletedTaskId = $task->id;
        $task->delete();

        $this->open = false;
        $this->taskId = null;
        $this->dispatch('task-deleted', taskId: $deletedTaskId);
        session()->flash('success', 'Task deleted.');
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
            ? Task::with(['project', 'sprint', 'assignee', 'agent.runtime', 'comments.author', 'comments.media', 'comments.agent', 'checklists', 'media'])->find($this->taskId)
            : null;

        $canEdit = [
            'meta'          => ! $task || Gate::allows('editMeta', $task),
            'status'        => ! $task || Gate::allows('editStatus', $task),
            'priority'      => ! $task || Gate::allows('editPriority', $task),
            'project'       => ! $task || Gate::allows('editProject', $task),
            'assignee'      => ! $task || Gate::allows('editAssignee', $task),
            'dates'         => ! $task || Gate::allows('editDates', $task),
            'deleteTask'    => $task && Gate::allows('delete', $task),
            'deleteComment' => $task && Gate::allows('deleteComment', $task),
            'attachments'   => $task && (auth()->user()->hasPermission('tasks.edit_own') || auth()->user()->hasPermission('tasks.edit_any')),
        ];

        $canClaim = $task && Gate::allows('claim', $task);

        // The transition is legal from in-review; input is supplied by the dialog
        $canRequestChanges = $task
            && $task->status === 'in-review'
            && auth()->user()->hasPermission('tasks.edit_any');

        $projects = Project::orderBy('name')->get(['id', 'name', 'color', 'components']);
        $users    = User::orderBy('name')->get(['id', 'name', 'color', 'initials']);
        $agents   = Agent::query()
            ->where('owner_id', auth()->id())
            ->whereNull('archived_at')
            ->with('runtime:id,name,provider,status')
            ->orderBy('name')
            ->get();
        $columns  = KanbanColumn::orderBy('position')->get(['slug', 'name', 'color']);

        // Component dropdown options come from the selected project's configured list
        $selectedProject = $this->projectId ? $projects->firstWhere('id', (int) $this->projectId) : null;
        $componentOptions = collect($selectedProject?->components ?? [])
            ->map(fn (string $name) => ['id' => $name, 'label' => $name])
            ->values()
            ->all();

        // Sprint dropdown options are scoped to the selected project (same as the
        // sprint views: any project sprint is selectable, status shown in the label)
        $sprintOptions = $selectedProject
            ? Sprint::where('project_id', $selectedProject->id)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'status'])
            : collect();

        return view('livewire.task-modal', compact('task', 'projects', 'users', 'agents', 'columns', 'canEdit', 'canClaim', 'canRequestChanges', 'componentOptions', 'sprintOptions'));
    }

    private function syncAgentQueue(Task $task, ?string $newAgentId, ?string $oldAgentId): void
    {
        if ((string) ($newAgentId ?? '') === (string) ($oldAgentId ?? '')) {
            return;
        }

        AgentTaskQueue::query()
            ->where('task_id', $task->id)
            ->whereIn('status', ['queued', 'dispatched'])
            ->update(['status' => 'cancelled']);

        if (! $newAgentId) {
            return;
        }

        $agent = Agent::query()
            ->where('id', $newAgentId)
            ->whereNull('archived_at')
            ->first();

        if (! $agent) {
            return;
        }

        $task->loadMissing('checklists');

        AgentTaskQueue::create([
            'task_id'    => $task->id,
            'agent_id'   => $agent->id,
            'runtime_id' => $agent->runtime_id,
            'team_id'    => $agent->team_id,
            'status'     => 'queued',
            'prompt'     => AgentTaskQueue::buildPrompt($task),
        ]);
    }
}
