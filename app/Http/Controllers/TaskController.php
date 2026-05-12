<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class TaskController extends Controller
{
    public function index() {}
    public function create() {}
    public function show(string $id) {}
    public function edit(string $id) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|string',
            'priority'    => 'required|in:high,medium,low',
            'project_id'  => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        Task::create($validated);

        return back();
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'sometimes|required|string',
            'priority'    => 'sometimes|required|in:high,medium,low',
            'project_id'  => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $task->update($validated);

        return back();
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return back();
    }
}
