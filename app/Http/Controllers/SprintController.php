<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
        ]);

        $data['project_id'] = $project->id;
        $data['sort_order'] = $project->sprints()->max('sort_order') + 1;
        $data['status']     = 'draft';

        $project->sprints()->create($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Sprint created.');
    }

    public function update(Request $request, Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
        ]);

        $sprint->update($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Sprint updated.');
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $sprint->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Sprint deleted.');
    }

    public function publish(Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $taskCount = $sprint->tasks()->count();
        if ($taskCount === 0) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'Cannot publish an empty sprint. Add tasks first.');
        }

        $openCount = $sprint->tasks()->where('status', 'open')->count();
        if ($openCount === 0) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'No open tasks to publish. Promote backlog items to this sprint first.');
        }

        $sprint->publish();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Sprint published. Tasks are now visible to developers.');
    }

    public function unpublish(Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $sprint->unpublish();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Sprint moved back to draft.');
    }

    public function complete(Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $unfinishedCount = $sprint->tasks()
            ->whereIn('status', ['in-progress', 'in-review'])
            ->count();

        if ($unfinishedCount > 0) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'Sprint has unfinished tasks. Complete or reassign them before closing the sprint.');
        }

        $sprint->complete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Sprint marked as completed.');
    }
}
