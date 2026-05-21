<?php

namespace App\Livewire;

use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\TaskClaimedNotification;
use App\Notifications\TaskStatusChangedNotification;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class KanbanBoard extends Component
{
    public string $activeTab = 'board';
    public ?int $filterProject = null;
    public ?int $filterAssignee = null;
    public string $filterPriority = '';

    public function moveTask(int $taskId, string $columnSlug): void
    {
        $task      = Task::findOrFail($taskId);
        Gate::authorize('editStatus', $task);
        $col       = KanbanColumn::where('slug', $columnSlug)->firstOrFail();
        $oldStatus = $task->status;
        $newStatus = $col->slug;

        if ($oldStatus === $newStatus) {
            return;
        }

        $task->update(['status' => $newStatus]);

        // Track completion timestamp
        if ($newStatus === 'done') {
            $task->completed_at = now();
            $task->saveQuietly();
        } elseif ($oldStatus === 'done') {
            $task->completed_at = null;
            $task->saveQuietly();
        }

        $mover      = auth()->user();
        $recipients = collect();
        if ($task->assigned_to && $task->assigned_to !== $mover->id) {
            $recipients->push($task->assignee);
        }
        $managers   = User::whereHas('roleModel', fn($q) => $q->whereIn('slug', ['super-admin', 'manager']))->get();
        $recipients = $recipients->merge($managers)->unique('id')->filter(fn($u) => $u->id !== $mover->id);

        $task->load('project');
        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskStatusChangedNotification($task, $oldStatus, $newStatus, $mover));
        }

        SlackNotificationHelper::notifyOnce(new TaskStatusChangedNotification($task, $oldStatus, $newStatus, $mover));

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
        $task->status      = 'todo';
        $task->save();

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->log('claimed task');

        session()->flash('success', 'You are now assigned to this task.');

        $task->load('project');
        $managers = User::whereHas('roleModel', fn($q) => $q->whereIn('slug', ['super-admin', 'manager']))->get();
        foreach ($managers as $manager) {
            $manager->notify(new TaskClaimedNotification($task, $user));
        }

        SlackNotificationHelper::notifyOnce(new TaskClaimedNotification($task, $user));

        $this->dispatch('task-claimed', taskId: $taskId);
    }

    public function render()
    {
        $user         = auth()->user();
        $scopedToUser = !$user->hasPermission('tasks.view_all');
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

        $teamProjectIds = $scopedToUser
            ? Team::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                  ->with('projects:id')
                  ->get()
                  ->flatMap(fn($t) => $t->projects->pluck('id'))
            : collect();

        $columns = KanbanColumn::orderBy('position')->get()->map(function ($col) use ($priorityOrder, $scopedToUser, $user, $teamProjectIds) {
            // Developers never see the Open column — it belongs to the Lobby
            if ($scopedToUser && $col->slug === 'open') {
                return null;
            }

            $query = $col->tasks()->with(['project', 'assignee', 'checklists'])->withCount('comments');

            // Developers: only their own assigned tasks (not team-wide unclaimed tasks)
            if ($scopedToUser) {
                $query->where('assigned_to', $user->id);
            }

            if ($this->filterProject) {
                $query->where('project_id', $this->filterProject);
            }
            if ($this->filterAssignee) {
                $query->where('assigned_to', $this->filterAssignee);
            }
            if ($this->filterPriority) {
                $query->where('priority', $this->filterPriority);
            }
            $tasks = $query->get()->sortBy(fn ($t) => $priorityOrder[$t->priority] ?? 9);
            $col->setRelation('tasks', $tasks->values());
            return $col;
        })->filter()->values();

        // Scoped users see projects where they have assigned tasks OR their teams are assigned
        if ($scopedToUser) {
            $projects = \App\Models\Project::with(['customer', 'tasks.assignee'])
                ->where(function ($q) use ($user, $teamProjectIds) {
                    $q->whereHas('tasks', fn($q2) => $q2->where('assigned_to', $user->id))
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
            $projects = \App\Models\Project::with(['tasks', 'customer'])->orderBy('name')->get();
        }

        $teamMembers = $scopedToUser
            ? \App\Models\User::where('id', $user->id)->get()
            : \App\Models\User::orderBy('name')->get();

        // Teams the current user belongs to (always loaded; only shown for scoped/developer)
        $userTeams = $scopedToUser
            ? Team::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                  ->with(['lead', 'members', 'projects'])
                  ->withCount('members')
                  ->orderBy('name')
                  ->get()
            : collect();

        return view('livewire.kanban-board', compact('columns', 'projects', 'teamMembers', 'scopedToUser', 'userTeams'));
    }
}
