<?php

namespace App\Livewire;

use App\Exceptions\InvalidTaskTransition;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\TaskClaimedNotification;
use App\Services\TaskStatusService;
use App\Support\Broadcasts\IssueBroadcastPayload;
use App\WebSocket\WebSocketBroadcaster;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class KanbanBoard extends Component
{
    use IssueBroadcastPayload;

    public array $activeQueueStatuses = ['queued', 'dispatched', 'running', 'waiting_local_directory'];

    public string $activeTab = 'board';

    public array $projectIds = [];

    public ?int $filterAssignee = null;

    public string $filterPriority = '';

    public array $filterComponents = [];

    public ?string $filterLabel = null;

    #[Url(as: 'projects', except: '')]
    public string $projectFilter = '';

    public function mount(): void
    {
        $this->projectIds = $this->parseProjectIds($this->projectFilter);
    }

    public function updatedProjectFilter(string $value): void
    {
        $this->projectIds = $this->parseProjectIds($value);
    }

    public function updatedProjectIds($value): void
    {
        $normalized = collect((array) $value)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->projectIds = $normalized;

        $csv = implode(',', $normalized);
        if ($this->projectFilter !== $csv) {
            $this->projectFilter = $csv;
        }
    }

    public function applyStoredProjectSelection(string $csv): void
    {
        if ($this->projectFilter !== '') {
            return;
        }

        $ids = $this->parseProjectIds($csv);
        if ($ids === []) {
            return;
        }

        $this->projectIds = $ids;
        $this->projectFilter = implode(',', $ids);
    }

    /**
     * @return list<int>
     */
    private function parseProjectIds(?string $csv): array
    {
        if (! is_string($csv) || trim($csv) === '') {
            return [];
        }

        return collect(explode(',', $csv))
            ->map(fn (string $value) => (int) trim($value))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function moveTask(int $taskId, string $columnSlug): void
    {
        $task = Task::findOrFail($taskId);
        Gate::authorize('editStatus', $task);
        $col = KanbanColumn::where('slug', $columnSlug)->firstOrFail();
        $newStatus = $col->slug;

        if ($task->status === $newStatus) {
            return;
        }

        // Moving into Changes Requested always requires a reason — drag-and-drop
        // cannot supply one, so point the reviewer at the Request changes dialog.
        if ($newStatus === 'changes-requested') {
            $this->dispatch(
                'board-toast',
                message: 'Requesting changes requires a reason. Open the task and use "Request changes" to pick a category and explain what needs to change.',
                type: 'error',
                taskId: $taskId,
            );
            $this->dispatch('$refresh');

            return;
        }

        try {
            app(TaskStatusService::class)->transition($task, $newStatus);
        } catch (InvalidTaskTransition $e) {
            $this->dispatch('board-toast', message: $e->getMessage(), type: 'error', taskId: $taskId);
            $this->dispatch('$refresh');

            return;
        }

        $workspaceId = $this->resolveWorkspaceId($task);

        if ($workspaceId) {
            WebSocketBroadcaster::broadcastIssueUpdated(
                $task, $workspaceId, (string) auth()->id()
            );
        }

        $this->dispatch('task-moved', taskId: $taskId, column: $columnSlug);
    }

    public function claimTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        Gate::authorize('claim', $task);

        if ($task->assigned_to !== null) {
            session()->flash('error', 'This task is already assigned to someone.');

            return;
        }

        $user = auth()->user();
        $task->assigned_to = $user->id;
        $task->save();
        app(TaskStatusService::class)->transition($task, 'todo');

        $workspaceId = $this->resolveWorkspaceId($task);

        if ($workspaceId) {
            WebSocketBroadcaster::broadcastIssueUpdated(
                $task, $workspaceId, (string) $user->id
            );
        }

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->log('claimed task');

        session()->flash('success', 'You are now assigned to this task.');

        $task->load('project');
        $managers = User::whereHas('roleModel', fn ($q) => $q->whereIn('slug', ['super-admin', 'manager']))->get();
        foreach ($managers as $manager) {
            $manager->notify(new TaskClaimedNotification($task, $user));
        }

        SlackNotificationHelper::notifyOnce(new TaskClaimedNotification($task, $user));

        $this->dispatch('task-claimed', taskId: $taskId);
    }

    #[On('taskStatusChanged')]
    public function onTaskStatusChanged(int $taskId, string $status): void
    {
        $this->dispatch('$refresh');
    }

    #[On('taskAgentStarted')]
    public function onTaskAgentStarted(int $taskId): void
    {
        $this->dispatch('$refresh');
    }

    #[On('taskAgentCompleted')]
    public function onTaskAgentCompleted(int $taskId): void
    {
        $this->dispatch('$refresh');
    }

    #[On('taskAgentFailed')]
    public function onTaskAgentFailed(int $taskId): void
    {
        $this->dispatch('$refresh');
    }

    #[On('task-deleted')]
    public function onTaskDeleted(int $taskId): void
    {
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $user = auth()->user();
        $scopedToUser = ! $user->hasPermission('tasks.view_all');
        $canViewBilling = $user->hasPermission('invoices.view');
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

        $teamProjectIds = $scopedToUser
            ? Team::whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->with('projects:id')
                ->get()
                ->flatMap(fn ($t) => $t->projects->pluck('id'))
            : collect();

        $columns = KanbanColumn::orderBy('position')->get()->map(function ($col) use ($priorityOrder, $scopedToUser, $user, $canViewBilling) {
            // Developers never see the Open column — it belongs to the Lobby
            if ($scopedToUser && $col->slug === 'open') {
                return null;
            }

            $relations = [
                'project',
                'assignee',
                'agent',
                'checklists',
                'latestQueue' => fn ($q) => $q->whereIn('status', $this->activeQueueStatuses),
            ];

            if ($canViewBilling) {
                $relations[] = 'activeInvoiceItem.invoice';
                $relations[] = 'invoiceOverride';
            }

            $query = $col->tasks()
                ->with($relations)
                ->withCount('comments')
                ->orderByDesc('updated_at');

            // Developers: only their own assigned tasks (not team-wide unclaimed tasks)
            if ($scopedToUser) {
                $query->where('assigned_to', $user->id);
            }

            if ($this->projectIds !== []) {
                $query->whereIn('project_id', $this->projectIds);
            }
            if ($this->filterAssignee) {
                $query->where('assigned_to', $this->filterAssignee);
            }
            if ($this->filterPriority) {
                $query->where('priority', $this->filterPriority);
            }
            if ($this->filterComponents !== []) {
                $query->whereIn('component', $this->filterComponents);
            }
            if ($this->filterLabel) {
                $query->whereJsonContains('labels', $this->filterLabel);
            }
            $tasks = $query->get()->sortBy(fn ($t) => $priorityOrder[$t->priority] ?? 9);
            $col->setRelation('tasks', $tasks->values());

            return $col;
        })->filter()->values();

        // Scoped users see projects where they have assigned tasks OR their teams are assigned
        if ($scopedToUser) {
            $projects = Project::with(['customer', 'tasks.assignee'])
                ->where(function ($q) use ($user, $teamProjectIds) {
                    $q->whereHas('tasks', fn ($q2) => $q2->where('assigned_to', $user->id))
                        ->orWhereIn('id', $teamProjectIds);
                })
                ->orderBy('name')
                ->get()
                // Tag each project so the view knows if it's a direct or team project
                ->each(function ($project) use ($user, $teamProjectIds) {
                    $project->is_team_project = $teamProjectIds->contains($project->id);
                    $project->my_tasks = $project->tasks->where('assigned_to', $user->id)->values();
                    $project->claimable_count = $project->tasks
                        ->where('status', 'open')
                        ->whereNull('assigned_to')
                        ->count();
                });
        } else {
            $projects = Project::with(['tasks', 'customer'])->orderBy('name')->get();
        }

        $teamMembers = $scopedToUser
            ? User::where('id', $user->id)->get()
            : User::orderBy('name')->get();

        // Component filter options are project-scoped: they follow the selected projects
        $filterScopeProjects = $this->projectIds !== []
            ? $projects->whereIn('id', $this->projectIds)
            : $projects;
        $componentFilterOptions = $filterScopeProjects
            ->flatMap(fn ($project) => $project->components ?? [])
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $name) => ['id' => $name, 'label' => $name])
            ->all();

        // Label filter options: all labels currently in use on tasks
        $labelOptions = Task::query()
            ->whereNotNull('labels')
            ->get(['labels'])
            ->flatMap(fn (Task $task) => $task->labels ?? [])
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $label) => ['id' => $label, 'label' => $label])
            ->all();

        // Teams the current user belongs to (always loaded; only shown for scoped/developer)
        $userTeams = $scopedToUser
            ? Team::whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->with(['lead', 'members', 'projects'])
                ->withCount('members')
                ->orderBy('name')
                ->get()
            : collect();

        return view('livewire.kanban-board', compact('columns', 'projects', 'teamMembers', 'scopedToUser', 'userTeams', 'canViewBilling', 'componentFilterOptions', 'labelOptions'));
    }
}
