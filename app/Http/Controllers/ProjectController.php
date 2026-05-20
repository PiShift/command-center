<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
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
        $sort      = in_array($request->sort, $sortable) ? $request->sort : 'name';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $query = Project::with('customer')
            ->withCount(['tasks', 'tasks as open_tasks_count' => fn ($q) => $q->where('status', '!=', 'done')])
            ->orderBy($sort, $direction);

        // Developers only see projects where their team is assigned
        if (! $user->hasPermission('projects.view_all')) {
            $userTeamIds = $user->teams()->pluck('teams.id');
            $query->whereHas('teams', fn ($q) => $q->whereIn('teams.id', $userTeamIds));
        }

        if ($request->filled('search'))   $query->where('name', 'like', '%' . $request->search . '%');
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('health'))   $query->where('health', $request->health);
        if ($request->filled('customer')) $query->where('customer_id', $request->customer);

        $projects  = $query->paginate(25)->withQueryString();
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

        $project->load(['customer', 'teams.members', 'tasks', 'tasks.assignee', 'sprints.tasks', 'backlogItems.sprint', 'backlogItems.promotedTask']);

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

        $recentTasks = $allTasks->sortByDesc('updated_at')->take(5)->values();

        $sprints = $canManage
            ? $project->sprints
            : $project->sprints->whereIn('status', ['active', 'completed'])->values();

        $backlogItems = $project->backlogItems->where('promoted', false)->values();

        $allTeams = \App\Models\Team::orderBy('name')->get(['id', 'name']);

        // Developer-specific task lists
        $availableTasks = collect();
        $myTasks        = collect();
        if (! $canManage) {
            $activeSprint = $sprints->firstWhere('status', 'active');
            if ($activeSprint) {
                $availableTasks = Task::where('project_id', $project->id)
                    ->where('sprint_id', $activeSprint->id)
                    ->where('status', 'open')
                    ->whereNull('assigned_to')
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
            'progressPercent', 'teamMembers', 'assignedTeams', 'recentTasks', 'sprints', 'backlogItems', 'allTeams',
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
