<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTaskMessage;
use App\Models\AgentTaskQueue;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentTaskController extends Controller
{
    public function messages(Request $request, string $queueId): JsonResponse
    {
        $queue = AgentTaskQueue::query()
            ->with('task.project')
            ->where('id', $queueId)
            ->first();

        if (! $queue || ! $queue->task || ! $queue->task->project) {
            return response()->json(['error' => 'task not found'], 404);
        }

        if (! $this->canAccessQueue($request, $queue)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $messages = AgentTaskMessage::query()
            ->where('task_queue_id', $queue->id)
            ->orderBy('seq')
            ->orderBy('id')
            ->get();

        return response()->json([
            'messages' => $messages->map(static function (AgentTaskMessage $message): array {
                return [
                    'id'         => (int) $message->id,
                    'seq'        => (int) $message->seq,
                    'type'       => $message->type,
                    'tool'       => $message->tool,
                    'content'    => $message->content,
                    'input'      => $message->input,
                    'output'     => $message->output,
                    'created_at' => optional($message->created_at)?->toIso8601String(),
                ];
            })->values(),
            'total' => $messages->count(),
        ]);
    }

    private function canAccessQueue(Request $request, AgentTaskQueue $queue): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->hasPermission('projects.view_all')) {
            return true;
        }

        return Project::query()
            ->whereKey($queue->task->project_id)
            ->whereHas('teams', function ($teamQuery) use ($user): void {
                $teamQuery->whereHas('members', function ($memberQuery) use ($user): void {
                    $memberQuery->where('users.id', $user->id);
                });
            })
            ->exists();
    }
}
