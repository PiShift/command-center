<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\ProjectHealthChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('projects.view'), 403);

        $sortable  = ['name', 'status', 'deadline', 'created_at'];
        $sort      = in_array($request->sort, $sortable) ? $request->sort : null; // null = activity sort
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $query = Project::with([
                'customer',
                'sprints:id,project_id,name,status,deadline',
                'tasks:id,project_id,status',
            ])
            ->withCount(['tasks', 'tasks as open_tasks_count' => fn ($q) => $q->where('status', '!=', 'done')]);

        // Developers only see projects where their team is assigned
        if (! $user->hasPermission('projects.view_all')) {
            $userTeamIds = $user->teams()->pluck('teams.id');
            $query->whereHas('teams', fn ($q) => $q->whereIn('teams.id', $userTeamIds));
        }

        if ($request->filled('search'))   $query->where('name', 'like', '%' . $request->search . '%');
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('health'))   $query->where('health', $request->health);
        if ($request->filled('customer')) $query->where('customer_id', $request->customer);

        if ($sort) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Fetch all (no pagination yet) to allow collection-level sort by activity state
        $allProjects = $query->get()->map(function ($project) {
            $sprints      = $project->sprints;
            $tasks        = $project->tasks;
            $activeSprint = $sprints->firstWhere('status', 'active');
            $draftSprint  = $sprints->firstWhere('status', 'draft');

            $activeStatuses = ['open', 'todo', 'in-progress', 'in-review'];
            $hasActiveTasks = $tasks->whereIn('status', $activeStatuses)->isNotEmpty();

            if ($activeSprint && $hasActiveTasks) {
                $activityState = 'active_sprint';
            } elseif ($draftSprint && !$activeSprint) {
                $activityState = 'preparing';
            } elseif ($sprints->isEmpty()) {
                $activityState = 'no_sprints';
            } else {
                $activityState = 'idle';
            }

            $doneCount  = $tasks->where('status', 'done')->count();
            $totalCount = $tasks->count();

            $project->activity_state              = $activityState;
            $project->active_sprint_name          = $activeSprint?->name ?? ($activityState === 'preparing' ? $draftSprint?->name : null);
            $project->active_sprint_is_draft      = !$activeSprint && $activityState === 'preparing';
            $project->active_sprint_deadline      = $activeSprint?->deadline;
            $project->active_sprint_days_remaining = $activeSprint?->deadline
                ? (int) now()->diffInDays($activeSprint->deadline, false)
                : null;
            $project->tasks_done_count            = $doneCount;
            $project->tasks_total_count           = $totalCount;
            $project->tasks_progress_percent      = $totalCount > 0 ? round($doneCount / $totalCount * 100) : 0;

            return $project;
        });

        // Sort by activity priority if no manual sort selected
        if (!$sort) {
            $priority = ['active_sprint' => 0, 'preparing' => 1, 'idle' => 2, 'no_sprints' => 3];
            $allProjects = $allProjects->sort(function ($a, $b) use ($priority) {
                $pa = $priority[$a->activity_state] ?? 9;
                $pb = $priority[$b->activity_state] ?? 9;
                if ($pa !== $pb) return $pa <=> $pb;
                return strcmp($a->name, $b->name);
            })->values();
        }

        // Manual paginate the sorted collection
        $page     = $request->input('page', 1);
        $perPage  = 25;
        $projects = new \Illuminate\Pagination\LengthAwarePaginator(
            $allProjects->forPage($page, $perPage),
            $allProjects->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('projects.index', compact('projects', 'customers', 'sort', 'direction'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('projects.create'), 403);
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        return view('projects.form', ['project' => null, 'customers' => $customers]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('projects.create'), 403);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'customer_id'   => 'nullable|exists:customers,id',
            'description'   => 'nullable|string',
            'guide'         => 'nullable|string',
            'github_repo'   => 'nullable|string|max:255',
            'stack'         => 'nullable|string|max:255',
            'slack_channel' => 'nullable|string|max:100',
            'color'         => 'nullable|string|max:20',
            'status'        => 'required|in:active,paused,complete',
            'start_date'    => 'nullable|date',
            'deadline'      => 'nullable|date',
            'budget'        => 'nullable|numeric|min:0',
            'health'        => 'required|in:on-track,at-risk,blocked',
        ]);

        $project = Project::create($data);
        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $user = auth()->user();
        Gate::authorize('view', $project);

        $project->load(['customer', 'teams.members', 'tasks.assignee']);

        $canManage = $user->can('manage', $project);

        $allTasks    = $project->tasks;
        $totalTasks  = $allTasks->count();
        $doneTasks   = $allTasks->where('status', 'done')->count();
        $openTasks   = $totalTasks - $doneTasks;
        $overdueTasks = $allTasks->filter(fn ($t) =>
            $t->status !== 'done' && $t->due_date && $t->due_date->isPast()
        )->count();

        $progressPercent = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

        $teamMembers = $project->teams
            ->flatMap(fn ($team) => $team->members)
            ->merge($allTasks->pluck('assignee')->filter())
            ->unique('id')
            ->values();

        $assignedTeams = $project->teams;

        $recentTasks = $allTasks->sortByDesc('updated_at')->take(8)->values();

        $allTeams = \App\Models\Team::orderBy('name')->get(['id', 'name']);

        // Developer-specific task lists
        $availableTasks = collect();
        $myTasks        = collect();
        if (! $canManage) {
            $activeSprintIds = \App\Models\Sprint::where('project_id', $project->id)
                ->where('status', 'active')
                ->pluck('id');
            if ($activeSprintIds->isNotEmpty()) {
                $availableTasks = Task::where('project_id', $project->id)
                    ->whereIn('sprint_id', $activeSprintIds)
                    ->where('status', 'open')
                    ->whereNull('assigned_to')
                    ->with('sprint')
                    ->orderByDesc('weight')
                    ->get();
            }
            $myTasks = Task::where('project_id', $project->id)
                ->where('assigned_to', $user->id)
                ->where('status', '!=', 'done')
                ->orderByDesc('updated_at')
                ->get();
        }

        return view('projects.show', compact(
            'project', 'totalTasks', 'openTasks', 'doneTasks', 'overdueTasks',
            'progressPercent', 'teamMembers', 'assignedTeams', 'recentTasks', 'allTeams',
            'canManage', 'availableTasks', 'myTasks'
        ));
    }

    public function edit(Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        return view('projects.form', compact('project', 'customers'));
    }

    public function update(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'customer_id'   => 'nullable|exists:customers,id',
            'description'   => 'nullable|string',
            'guide'         => 'nullable|string',
            'github_repo'   => 'nullable|string|max:255',
            'stack'         => 'nullable|string|max:255',
            'slack_channel' => 'nullable|string|max:100',
            'color'         => 'nullable|string|max:20',
            'status'        => 'required|in:active,paused,complete',
            'start_date'    => 'nullable|date',
            'deadline'      => 'nullable|date',
            'budget'        => 'nullable|numeric|min:0',
            'health'        => 'required|in:on-track,at-risk,blocked',
        ]);

        $oldHealth = $project->health;
        $project->update($data);

        if (isset($data['health']) && in_array($data['health'], ['at-risk', 'blocked']) && $data['health'] !== $oldHealth) {
            $managers = User::whereHas('roleModel', fn($q) => $q->whereIn('slug', ['super-admin', 'manager']))->get();
            foreach ($managers as $manager) {
                $manager->notify(new ProjectHealthChangedNotification($project, $data['health']));
            }

            SlackNotificationHelper::notifyOnce(new ProjectHealthChangedNotification($project, $data['health']));
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    public function assignTeams(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'teams'   => 'nullable|array',
            'teams.*' => 'integer|exists:teams,id',
        ]);

        $project->teams()->sync($data['teams'] ?? []);

        return back()->with('success', 'Teams updated.');
    }

    public function destroy(Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.delete'), 403);
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}
