<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teams = $request->user()
            ->teams()
            ->select('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();

        $workspaces = $teams->map(static function ($team): array {
            return [
                'id'   => (string) $team->id,
                'name' => $team->name,
                'slug' => \Illuminate\Support\Str::slug($team->name),
                'type' => 'local',
            ];
        })->values();

        return response()->json($workspaces);
    }

    public function show(string $id): JsonResponse
    {
        $team = Team::find($id);

        if (! $team) {
            return response()->json(['error' => 'not found'], 404);
        }

        return response()->json([
            'id'          => (string) $team->id,
            'name'        => $team->name,
            'slug'        => Str::slug($team->name),
            'description' => $team->description,
            'created_at'  => $team->created_at->toIso8601String(),
            'updated_at'  => $team->updated_at->toIso8601String(),
        ]);
    }
}
