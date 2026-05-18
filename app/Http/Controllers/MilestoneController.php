<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
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
        $data['sort_order'] = $project->milestones()->max('sort_order') + 1;

        $project->milestones()->create($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Milestone created.');
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($milestone->project_id === $project->id, 404);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
        ]);

        $milestone->update($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Milestone updated.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($milestone->project_id === $project->id, 404);

        $milestone->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Milestone deleted.');
    }
}
