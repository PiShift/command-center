<?php

namespace App\Http\Controllers;

use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class BacklogItemController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'guide'        => 'nullable|string',
            'sprint_id' => 'nullable|exists:sprints,id',
        ]);

        // Ensure the sprint belongs to this project
        if (!empty($data['sprint_id'])) {
            abort_unless(
                $project->sprints()->where('id', $data['sprint_id'])->exists(),
                422
            );
        }

        $data['project_id'] = $project->id;
        $data['sort_order'] = $project->backlogItems()->max('sort_order') + 1;

        // Auto-refine if guide or description is provided
        if (!empty($data['guide']) || !empty($data['description'])) {
            $data['status'] = 'refined';
        } else {
            $data['status'] = 'raw';
        }

        $project->backlogItems()->create($data);

        return redirect()->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', 'Backlog item added.');
    }

    public function update(Request $request, Project $project, BacklogItem $backlogItem)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($backlogItem->project_id === $project->id, 404);

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'guide'        => 'nullable|string',
            'sprint_id' => 'nullable|exists:sprints,id',
        ]);

        // Ensure the sprint belongs to this project
        if (!empty($data['sprint_id'])) {
            abort_unless(
                $project->sprints()->where('id', $data['sprint_id'])->exists(),
                422
            );
        }

        // Auto-refine if guide or description is provided
        if (!empty($data['guide']) || !empty($data['description'])) {
            $data['status'] = 'refined';
        }

        $backlogItem->update($data);

        return redirect()->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', 'Backlog item updated.');
    }

    public function destroy(Project $project, BacklogItem $backlogItem)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($backlogItem->project_id === $project->id, 404);

        $backlogItem->delete();

        return redirect()->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', 'Backlog item deleted.');
    }

    public function promote(Request $request, Project $project, BacklogItem $backlogItem)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($backlogItem->project_id === $project->id, 404);
        abort_if($backlogItem->promoted, 422);

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'type'         => 'required|in:bug,feature,change',
            'priority'     => 'required|in:low,medium,high',
            'sprint_id' => 'nullable|exists:sprints,id',
            'weight'       => 'required|integer|between:1,5',
            'assigned_to'  => 'nullable|exists:users,id',
            'due_date'     => 'nullable|date',
        ]);

        // Ensure sprint belongs to this project
        if (!empty($data['sprint_id'])) {
            abort_unless(
                $project->sprints()->where('id', $data['sprint_id'])->exists(),
                422
            );
        }

        $task = Task::create([
            'project_id'   => $project->id,
            'sprint_id'    => $data['sprint_id'] ?? null,
            'assigned_to'  => $data['assigned_to'] ?? null,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'type'         => $data['type'],
            'priority'     => $data['priority'],
            'status'       => 'open',
            'weight'       => $data['weight'],
            'due_date'     => $data['due_date'] ?? null,
            'source'       => 'manual',
        ]);

        $backlogItem->update([
            'promoted'         => true,
            'promoted_task_id' => $task->id,
            'promoted_at'      => now(),
            'sprint_id'        => $data['sprint_id'] ?? $backlogItem->sprint_id,
        ]);

        return redirect()->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', 'Backlog item promoted to task successfully.');
    }

    public function bulkSprint(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'items'        => 'required|array|min:1',
            'items.*'      => 'integer|exists:backlog_items,id',
            'sprint_id' => 'nullable|exists:sprints,id',
        ]);

        if (!empty($data['sprint_id'])) {
            abort_unless(
                $project->sprints()->where('id', $data['sprint_id'])->exists(),
                422
            );
        }

        BacklogItem::whereIn('id', $data['items'])
            ->where('project_id', $project->id)
            ->where('promoted', false)
            ->update(['sprint_id' => $data['sprint_id'] ?? null]);

        return redirect()->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', 'Sprint assigned to selected items.');
    }

    public function bulkPromote(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'items'   => 'required|array|min:1',
            'items.*' => 'integer|exists:backlog_items,id',
        ]);

        $items = BacklogItem::whereIn('id', $data['items'])
            ->where('project_id', $project->id)
            ->where('promoted', false)
            ->get();

        foreach ($items as $item) {
            $task = Task::create([
                'project_id'   => $project->id,
                'sprint_id'    => $item->sprint_id,
                'assigned_to'  => null,
                'title'        => $item->title,
                'description'  => $item->description,
                'type'         => 'feature',
                'priority'     => 'medium',
                'status'       => 'open',
                'weight'       => 3,
                'source'       => 'manual',
            ]);

            $item->update([
                'promoted'         => true,
                'promoted_task_id' => $task->id,
                'promoted_at'      => now(),
            ]);
        }

        return redirect()->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', count($items) . ' item(s) promoted to tasks.');
    }

    public function bulkDelete(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'items'   => 'required|array|min:1',
            'items.*' => 'integer|exists:backlog_items,id',
        ]);

        $deleted = BacklogItem::whereIn('id', $data['items'])
            ->where('project_id', $project->id)
            ->where('promoted', false)
            ->delete();

        return redirect()->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', $deleted . ' item(s) deleted.');
    }
}
