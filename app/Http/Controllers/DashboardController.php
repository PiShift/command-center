<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        // Developers have no access to the war room — redirect to board
        if (auth()->user()?->roleModel?->slug === 'developer') {
            return redirect()->route('board');
        }

        $activeProjects  = Project::where('status', 'active')->count();
        $blockedProjects = Project::where('health', 'blocked')->count();
        $atRiskProjects  = Project::where('health', 'at-risk')->count();
        $openTasks       = Task::where('status', '!=', 'done')->count();
        $inProgressTasks = Task::where('status', 'in-progress')->count();
        $overdueTasks    = Task::where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->count();
        $highPrioTasks   = Task::where('status', '!=', 'done')->where('priority', 'high')->count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $doneTasks       = Task::where('status', 'done')
            ->whereDate('completed_at', '>=', now()->subDays(7))
            ->count();

        $myTasks = Task::with('project')
            ->where('assigned_to', auth()->id())
            ->where('status', '!=', 'done')
            ->orderBy('due_date')
            ->get();

        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('dashboard.index', compact(
            'activeProjects', 'blockedProjects', 'atRiskProjects',
            'openTasks', 'inProgressTasks', 'overdueTasks', 'highPrioTasks',
            'activeCustomers', 'doneTasks', 'myTasks', 'projects'
        ));
    }
}
