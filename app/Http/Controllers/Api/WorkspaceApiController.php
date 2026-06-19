<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json(['workspaces' => $workspaces]);
    }
}
