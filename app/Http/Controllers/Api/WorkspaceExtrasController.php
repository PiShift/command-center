<?php

namespace App\Http\Controllers\Api;

use App\Models\Team;
use Illuminate\Http\JsonResponse;

class WorkspaceExtrasController
{
    public function update(string $id): JsonResponse
    {
        $team = Team::query()->whereKey((int) $id)->first();
        if (! $team) {
            return response()->json(['error' => 'not found'], 404);
        }

        return response()->json([
            'id' => (string) $team->id,
            'name' => $team->name,
            'slug' => \Illuminate\Support\Str::slug($team->name),
            'type' => 'local',
        ]);
    }

    public function destroy(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function leave(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function addMember(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function updateMember(string $id, string $memberId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function removeMember(string $id, string $memberId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function invitations(string $id): JsonResponse { return response()->json([]); }
    public function deleteInvitation(string $id, string $invId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function runtimeProfiles(string $id): JsonResponse { return response()->json(['profiles' => []]); }
    public function createRuntimeProfile(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function updateRuntimeProfile(string $id, string $profileId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function deleteRuntimeProfile(string $id, string $profileId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function githubConnect(string $id): JsonResponse { return response()->json(['url' => '']); }
    public function githubInstallations(string $id): JsonResponse { return response()->json(['installations' => []]); }
    public function deleteGithubInstallation(string $id, string $instId): JsonResponse { return response()->json(['status' => 'ok']); }
}
