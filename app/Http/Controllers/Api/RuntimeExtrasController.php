<?php

namespace App\Http\Controllers\Api;

use App\Models\AgentRuntime;
use Illuminate\Http\JsonResponse;

class RuntimeExtrasController
{

    public function usage(string $runtimeId): JsonResponse
    {
        $runtime = AgentRuntime::query()->whereKey($runtimeId)->first();
        if (!$runtime) {
            return response()->json([]);
        }

        $days = (int) request()->query('days', 14);

        $usageData = \App\Models\AgentTaskUsage::query()
            ->join('agent_task_queue', 'agent_task_usage.task_queue_id', '=', 'agent_task_queue.id')
            ->where('agent_task_queue.runtime_id', $runtimeId)
            ->where('agent_task_usage.created_at', '>=', now()->subDays($days))
            ->selectRaw('
                DATE(agent_task_usage.created_at) as date,
                COALESCE(agent_task_usage.model, "unknown") as model,
                SUM(agent_task_usage.input_tokens) as input_tokens,
                SUM(agent_task_usage.output_tokens) as output_tokens
            ')
            ->groupBy('date', 'model')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($usageData->map(fn($row) => [
            'runtime_id'         => $runtimeId,
            'date'               => $row->date,
            'provider'           => $runtime->provider,
            'model'              => $row->model,
            'input_tokens'       => (int) $row->input_tokens,
            'output_tokens'      => (int) $row->output_tokens,
            'cache_read_tokens'  => 0,
            'cache_write_tokens' => 0,
        ]));
    }
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
