<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskChecklist;
use App\Notifications\TaskChecklistCompletedNotification;
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

        // If all checklist items are now checked, notify the assignee
        if (isset($data['is_checked']) && $data['is_checked']) {
            $total   = $task->checklists()->count();
            $checked = $task->checklists()->where('is_checked', true)->count();

            if ($total > 0 && $total === $checked && $task->assignee) {
                $task->assignee->notify(new TaskChecklistCompletedNotification($task->load('project')));
            }
        }

        return back();
    }

    public function destroy(Task $task, TaskChecklist $item)
    {
        abort_unless($item->task_id === $task->id, 404);
        // Template-sourced items can be checked off but not deleted
        abort_if($item->isLocked(), 403);

        $item->delete();

        return back();
    }
}
