<?php

namespace App\Http\Controllers\Api;

use App\Models\AgentTaskMessage;
use Illuminate\Http\JsonResponse;

class ChatExtrasController
{
    public function createSession(): JsonResponse { return response()->json(['status' => 'ok']); }
    public function showSession(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function updateSession(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function deleteSession(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function messages(string $sessionId): JsonResponse { return response()->json([]); }
    public function messagesPage(string $sessionId): JsonResponse { return response()->json(['messages' => [], 'has_more' => false]); }
    public function sendMessage(string $sessionId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function pendingTask(string $sessionId): JsonResponse { return response()->json(['task' => null]); }
    public function readSession(string $sessionId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function cancelTask(string $taskId): JsonResponse { return response()->json(['status' => 'ok']); }

    public function taskMessages(string $taskId): JsonResponse
    {
        $messages = AgentTaskMessage::query()
            ->where('task_queue_id', $taskId)
            ->orderBy('seq')
            ->orderBy('id')
            ->get(['id', 'seq', 'type', 'tool', 'content', 'input', 'output', 'created_at']);

        return response()->json($messages);
    }
}
