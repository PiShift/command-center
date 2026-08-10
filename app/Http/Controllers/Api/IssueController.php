<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentTaskQueue;
use App\Models\Task;
use App\Models\TaskComment;
use App\Exceptions\InvalidTaskTransition;
use App\Services\AgentTriggerService;
use App\Services\TaskStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IssueController extends Controller
{
    public function __construct(private readonly IssuePayloadTransformer $issuePayloadTransformer = new IssuePayloadTransformer()) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        return response()->json($this->issuePayloadTransformer->transform($task, $request->user()?->id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        Log::info('Updating issue', ['id' => $id, 'payload' => $request->all()]);

        $data = $request->validate([
            'status'        => ['sometimes', 'string', 'max:100'],
            'priority'      => ['sometimes', 'string'],
            'assignee_id'   => ['sometimes', 'nullable', 'string'],
            'assignee_type' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('status', $data)) {
            $mappedStatus = $this->issuePayloadTransformer->normalizeIncomingStatus((string) $data['status']);

            if ($mappedStatus !== null && $mappedStatus !== (string) $task->status) {
                try {
                    app(TaskStatusService::class)->transition($task, $mappedStatus);
                } catch (InvalidTaskTransition $e) {
                    return response()->json(['error' => $e->getMessage()], 422);
                }
            }
        }

        if (array_key_exists('priority', $data)) {
            $priority = strtolower(trim((string) $data['priority']));

            if (in_array($priority, ['low', 'medium', 'high'], true)) {
                $task->priority = $priority;
            }
        }

        if (array_key_exists('assignee_id', $data) || array_key_exists('assignee_type', $data)) {
            $this->issuePayloadTransformer->applyIncomingAssignee(
                $task,
                $data['assignee_type'] ?? null,
                $data['assignee_id'] ?? null,
            );
        }

        Log::info('Saving issue', ['id' => $id, 'status' => $task->status, 'priority' => $task->priority, 'assigned_to' => $task->assigned_to]);

        if ($task->isDirty()) {
            $task->save();
        }

        return response()->json($this->issuePayloadTransformer->transform($task->fresh()));
    }

    public function comments(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        $comments = TaskComment::query()
            ->where('task_id', $task->id)
            ->with('author:id,name')
            ->orderBy('created_at')
            ->get();

        return response()->json(
            $comments->map(fn (TaskComment $comment): array => $this->commentPayload($comment))->values()
        );
    }

    public function storeComment(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        // Detect if this is a daemon call (mat_ token auth)
        $authHeader = $request->header('Authorization', '');
        $isDaemonCall = str_starts_with($authHeader, 'Bearer mat_');
        
        $commentData = [
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body'    => $data['content'],
        ];

        // If daemon call, also set agent_id from the task's agent
        if ($isDaemonCall && $task->agent_id) {
            $commentData['agent_id'] = $task->agent_id;
        }

        $comment = TaskComment::create($commentData);

        $workspaceId = (string) $task->project?->teams()?->first()?->id;

        \App\WebSocket\WebSocketBroadcaster::broadcastCommentCreated($comment, $workspaceId);

        // Trigger agent if not a daemon comment and task has agent assigned
        if (!$isDaemonCall) {
            AgentTriggerService::triggerOnComment($task, $comment);
        }

        return response()->json($this->commentPayload($comment), 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        Task::query()->whereKey($task->id)->delete();

        return response()->json(['status' => 'ok']);
    }

    private function resolveTask(string $id): ?Task
    {
        if (preg_match('/^[0-9a-f-]{36}$/i', $id) === 1) {
            return Task::query()->whereKey($id)->first();
        }

        if (str_contains($id, '-')) {
            $parts = explode('-', $id);
            $number = end($parts);

            if (is_numeric($number)) {
                return Task::query()->whereKey((int) $number)->first();
            }
        }

        if (is_numeric($id)) {
            return Task::query()->whereKey((int) $id)->first();
        }

        return null;
    }

    private function commentPayload(TaskComment $comment): array
    {
        $isAgentComment = ! is_null($comment->agent_id);

        return [
            'id'               => (string) $comment->id,
            'issue_id'         => (string) $comment->task_id,
            'author_type'      => $isAgentComment ? 'agent' : 'user',
            'author_id'        => $isAgentComment ? (string) $comment->agent_id : (string) $comment->user_id,
            'content'          => (string) $comment->body,
            'type'             => 'comment',
            'parent_id'        => null,
            'created_at'       => optional($comment->created_at)?->toIso8601String(),
            'updated_at'       => optional($comment->updated_at)?->toIso8601String(),
            'resolved_at'      => null,
            'resolved_by_type' => null,
            'resolved_by_id'   => null,
            'reactions'        => [],
            'attachments'      => [],
            'agent_id'         => $comment->agent_id ? (string) $comment->agent_id : null,
        ];
    }
}
