<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Http\Request;

class TaskChecklistController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $data = $request->validate([
            'label' => 'required|string|max:500',
        ]);

        $maxOrder = $task->checklists()->max('sort_order') ?? -1;

        $task->checklists()->create([
            'label'      => $data['label'],
            'is_checked' => false,
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    public function update(Request $request, Task $task, TaskChecklist $item)
    {
        abort_unless($item->task_id === $task->id, 404);

        $data = $request->validate([
            'label'      => 'sometimes|required|string|max:500',
            'is_checked' => 'sometimes|boolean',
        ]);

        $item->update($data);

        return back();
    }

    public function destroy(Task $task, TaskChecklist $item)
    {
        abort_unless($item->task_id === $task->id, 404);

        $item->delete();

        return back();
    }
}
