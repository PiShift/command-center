<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentExtrasController
{
    public function env(string $id): JsonResponse
    {
        return response()->json(['env' => (object) []]);
    }

    public function updateEnv(string $id): JsonResponse
    {
        return response()->json(['env' => (object) []]);
    }

    public function archive(string $id): JsonResponse
    {
        $agent = Agent::query()->whereKey($id)->first();
        if (! $agent) {
            return response()->json(['error' => 'not found'], 404);
        }

        $agent->update(['archived_at' => now()]);
        return response()->json($agent->fresh());
    }

    public function restore(string $id): JsonResponse
    {
        $agent = Agent::query()->whereKey($id)->first();
        if (! $agent) {
            return response()->json(['error' => 'not found'], 404);
        }

        $agent->update(['archived_at' => null]);
        return response()->json($agent->fresh());
    }

    public function cancelTasks(string $id): JsonResponse
    {
        return response()->json(['cancelled' => 0]);
    }

    public function tasks(string $id): JsonResponse
    {
        return response()->json([]);
    }

    public function skills(string $id): JsonResponse
    {
        return response()->json([]);
    }

    public function setSkills(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function addSkills(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function templates(): JsonResponse
    {
        return response()->json([]);
    }

    public function template(string $slug): JsonResponse
    {
        return response()->json(['error' => 'not found'], 404);
    }

    public function fromTemplate(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
