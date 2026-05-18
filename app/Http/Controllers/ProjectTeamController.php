<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;

class ProjectTeamController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $data = $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        if ($project->teams()->where('team_id', $data['team_id'])->exists()) {
            return back()->with('error', 'This team is already assigned to the project.');
        }

        $project->teams()->attach($data['team_id']);

        return back()->with('success', 'Team assigned to project.');
    }

    public function destroy(Project $project, Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $project->teams()->detach($team->id);

        return back()->with('success', 'Team detached from project.');
    }
}
