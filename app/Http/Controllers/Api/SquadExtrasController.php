<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class SquadExtrasController
{
    public function show(string $id): JsonResponse { return response()->json(['error' => 'not found'], 404); }
    public function store(): JsonResponse { return response()->json(['status' => 'ok']); }
    public function update(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function destroy(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function members(string $squadId): JsonResponse { return response()->json([]); }
    public function addMember(string $squadId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function removeMember(string $squadId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function updateMemberRole(string $squadId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function memberStatus(string $squadId): JsonResponse { return response()->json(['members' => []]); }
}
