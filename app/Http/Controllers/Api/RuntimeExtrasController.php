<?php

namespace App\Http\Controllers\Api;

use App\Models\AgentRuntime;
use Illuminate\Http\JsonResponse;

class RuntimeExtrasController
{
    public function destroy(string $runtimeId): JsonResponse
    {
        AgentRuntime::query()->whereKey($runtimeId)->delete();
        return response()->json(['status' => 'ok']);
    }

    public function update(string $runtimeId): JsonResponse
    {
        $runtime = AgentRuntime::query()->whereKey($runtimeId)->first();
        if (! $runtime) {
            return response()->json(['error' => 'not found'], 404);
        }

        return response()->json($runtime);
    }

    public function activity(string $runtimeId): JsonResponse { return response()->json([]); }
    public function usageByAgent(string $runtimeId): JsonResponse { return response()->json([]); }
    public function usageByHour(string $runtimeId): JsonResponse { return response()->json([]); }
    public function archiveAndDelete(string $runtimeId): JsonResponse { return response()->json(['status' => 'ok', 'agents_archived' => 0, 'tasks_cancelled' => 0]); }
    public function requestUpdate(string $runtimeId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function getUpdate(string $runtimeId, string $updateId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function requestModels(string $runtimeId): JsonResponse { return response()->json(['status' => 'ok', 'id' => '']); }
    public function getModels(string $runtimeId, string $requestId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function requestLocalSkills(string $runtimeId): JsonResponse { return response()->json(['status' => 'ok', 'id' => '']); }
    public function getLocalSkills(string $runtimeId, string $requestId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function importLocalSkill(string $runtimeId): JsonResponse { return response()->json(['status' => 'ok', 'id' => '']); }
    public function getLocalSkillImport(string $runtimeId, string $requestId): JsonResponse { return response()->json(['status' => 'ok']); }
}
