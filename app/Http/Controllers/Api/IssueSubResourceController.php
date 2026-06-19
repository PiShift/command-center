<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class IssueSubResourceController
{
    public function timeline(string $id): JsonResponse
    {
        return response()->json([]);
    }

    public function subscribers(string $id): JsonResponse
    {
        return response()->json([]);
    }

    public function usage(string $id): JsonResponse
    {
        return response()->json(['total_cost' => 0, 'total_tokens' => 0, 'runs' => []]);
    }

    public function attachments(string $id): JsonResponse
    {
        return response()->json([]);
    }

    public function labels(string $id): JsonResponse
    {
        return response()->json(['labels' => []]);
    }

    public function pullRequests(string $id): JsonResponse
    {
        return response()->json(['pull_requests' => []]);
    }

    public function taskRuns(string $id): JsonResponse
    {
        return response()->json([]);
    }

    public function children(string $id): JsonResponse
    {
        return response()->json(['issues' => []]);
    }

    public function activeTask(string $id): JsonResponse
    {
        return response()->json(['tasks' => []]);
    }

    public function cancelTask(string $issueId, string $taskId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function rerun(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
