<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teams = $request->user()->teams()->select('teams.id', 'teams.name')->orderBy('name')->get();

        $workspaces = [];
        foreach ($teams as $team) {
            $workspaces[] = [
                'id'   => (string) $team->id,
                'name' => $team->name,
                'type' => 'local',
            ];
        }

        return response()->json(['workspaces' => $workspaces]);
    }

    public function show(Request $request, string $workspaceId): JsonResponse
    {
        $teamId = (int) $workspaceId;
        $team = Team::find($teamId);

        if (! $team) {
            return response()->json(['error' => 'workspace not found'], 404);
        }

        if (! $team->members()->where('users.id', $request->user()->id)->exists()) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $projects = $team->projects()->select('projects.id', 'projects.name', 'projects.repos')->get();

        $projectData = [];
        foreach ($projects as $project) {
            $repos = is_array($project->repos) ? $project->repos : [];
            $projectData[] = [
                'id'    => (string) $project->id,
                'name'  => $project->name,
                'repos' => $repos,
            ];
        }

        return response()->json([
            'id'       => (string) $team->id,
            'name'     => $team->name,
            'type'     => 'local',
            'projects' => $projectData,
        ]);
    }
}