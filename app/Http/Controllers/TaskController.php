<?php

namespace App\Http\Controllers;

use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tasks.view'), 403);

        $sortable  = ['title', 'priority', 'status', 'due_date', 'created_at'];
        $sort      = in_array($request->sort, $sortable) ? $request->sort : 'due_date';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $query = Task::with(['project', 'assignee'])->orderBy($sort, $direction);

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('type'))     $query->where('type', $request->type);
        if ($request->filled('priority')) $query->where('priority', $request->priority);
        if ($request->filled('project'))  $query->where('project_id', $request->project);
        if ($request->filled('assignee')) $query->where('assigned_to', $request->assignee);
        if ($request->boolean('overdue')) {
            $query->whereNotNull('due_date')->whereDate('due_date', '<', now())->where('status', '!=', 'done');
        }
        if ($request->boolean('high_priority')) {
            $query->where('priority', 'high');
        }

        $tasks    = $query->paginate(30)->withQueryString();
        $columns  = KanbanColumn::orderBy('position')->get();
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $users    = User::orderBy('name')->get(['id', 'name']);

        return view('tasks.index', compact('tasks', 'columns', 'projects', 'users', 'sort', 'direction'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('tasks.create'), 403);

        $projects = Project::orderBy('name')->get(['id', 'name']);
        $users    = User::orderBy('name')->get(['id', 'name']);
        $columns  = KanbanColumn::orderBy('position')->get(['slug', 'name']);

        return view('tasks.form', ['task' => null, 'projects' => $projects, 'users' => $users, 'columns' => $columns]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tasks.create'), 403);

        $task = Task::create($this->validated($request));
        return redirect()->route('tasks.show', $task)->with('success', 'Task created.');
    }

    public function show(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.view'), 403);
        $task->load(['project', 'assignee', 'checklists', 'media', 'comments' => fn($q) => $q->with(['author', 'media'])->latest()]);
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        $projects = Project::orderBy('name')->get(['id', 'name']);
        $users    = User::orderBy('name')->get(['id', 'name']);
        $columns  = KanbanColumn::orderBy('position')->get(['slug', 'name']);

        return view('tasks.form', compact('task', 'projects', 'users', 'columns'));
    }

    public function update(Request $request, Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        $task->update($this->validated($request));
        return redirect()->route('tasks.show', $task)->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.delete'), 403);
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    public function advance(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        $next = match ($task->status) {
            'backlog'     => 'in-progress',
            'in-progress' => 'done',
            default       => 'backlog',
        };

        $task->update([
            'status'       => $next,
            'completed_at' => $next === 'done' ? now() : null,
        ]);

        return back()->with('success', 'Task status updated.');
    }

    public function claim(Task $task)
    {
        $user = auth()->user();
        \Illuminate\Support\Facades\Gate::authorize('claim', $task);

        if ($task->assigned_to !== null) {
            return back()->with('error', 'This task is already assigned to someone.');
        }

        $task->assigned_to = $user->id;
        $task->status      = 'todo';
        $task->save();

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->log("Task claimed by {$user->name} — status changed to todo");

        return back()->with('success', 'You are now assigned to this task.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'           => 'required|string|max:255',
            'project_id'      => 'required|exists:projects,id',
            'assigned_to'     => 'nullable|exists:users,id',
            'type'            => 'required|in:bug,feature,change',
            'priority'        => 'required|in:low,medium,high',
            'status'          => 'required|string',
            'due_date'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'labels'          => 'nullable|array',
            'labels.*'        => 'string|max:50',
            'description'     => 'nullable|string',
            'source'          => 'required|in:manual,ai-chat',
            'original_input'  => 'nullable|string',
            'guide'           => 'nullable|string',
        ]);
    }
}
