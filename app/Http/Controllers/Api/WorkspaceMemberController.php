<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, string $workspaceId): JsonResponse
    {
        $team = Team::query()
            ->with(['members' => function ($query): void {
                $query->select('users.id', 'users.name', 'users.email')->orderBy('users.id');
            }])
            ->whereKey((int) $workspaceId)
            ->first();

        if (! $team) {
            return response()->json([]);
        }

        $user = $request->user();

        if (! $user->hasPermission('projects.view_all') && ! $team->members()->where('users.id', $user->id)->exists()) {
            return response()->json([]);
        }

        return response()->json($team->members->map(function ($member) use ($team, $workspaceId): array {
            $actorId = 'member-' . (string) $member->id;

            return [
                'id'           => (string) $member->id,
                'workspace_id' => (string) $workspaceId,
                'actor_type'   => 'member',
                'actor_id'     => $actorId,
                'member_id'    => $actorId,
                'user_id'      => $actorId,
                'role'         => (int) $team->lead_user_id === (int) $member->id ? 'owner' : 'member',
                'created_at'   => optional($member->pivot?->created_at)?->toIso8601String(),
                'name'         => (string) $member->name,
                'email'        => (string) $member->email,
                'avatar_url'   => null,
            ];
        })->values());
    }
}
