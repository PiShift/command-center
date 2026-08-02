<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\KanbanColumn;

class WarRoomController extends Controller
{
    public function index()
    {
        $user = auth()->user()->load('roleModel.permissions');

        $can = [
            'viewAll'      => $user->hasPermission('tasks.view_all'),
            'create'       => $user->hasPermission('tasks.edit_any') || $user->hasPermission('tasks.create'),
            'editAny'      => $user->hasPermission('tasks.edit_any'),
            'delete'       => $user->hasPermission('tasks.delete'),
            'reassign'     => $user->hasPermission('tasks.reassign'),
            'seeTeam'      => $user->hasPermission('users.view'),
            'changeStatus' => $user->hasPermission('tasks.change_status'),
        ];

        $taskQuery = Task::with([
            'project:id,name,color',
            'assignee:id,name,initials,color',
        ])->orderBy('created_at', 'desc');

        if (! $can['viewAll']) {
            $taskQuery->where('assigned_to', $user->id);
        }

        $tasks = $taskQuery->get(['id', 'project_id', 'assigned_to', 'title', 'description', 'status', 'priority', 'labels', 'created_at']);

        if ($can['viewAll']) {
            $projects = Project::orderBy('name')->get(['id', 'name', 'description', 'stack', 'color', 'status']);
        } else {
            $projectIds = $tasks->pluck('project_id')->filter()->unique();
            $projects = Project::whereIn('id', $projectIds)->orderBy('name')->get(['id', 'name', 'description', 'stack', 'color', 'status']);
        }

        return Inertia::render('WarRoom/Index', [
            'projects' => $projects,
            'tasks'    => $tasks,
            'team'     => $can['seeTeam'] ? User::orderBy('name')->get(['id', 'name', 'initials', 'color']) : [],
            'statuses' => KanbanColumn::orderBy('position')->get(['id', 'name', 'slug', 'color', 'icon']),
            'can'      => $can,
        ]);
    }
}

