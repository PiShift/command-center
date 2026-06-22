<?php

namespace App\WebSocket;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Support\Facades\Redis;

class WebSocketBroadcaster
{
    private const CHANNEL = 'pishift:ws:events';

    /**
     * Publish an event to Redis — the WebSocket server subscribes
     * and forwards to connected clients.
     */
    public static function broadcastToWorkspace(string $workspaceId, array $payload): void
    {
        try {
            Redis::publish(self::CHANNEL, json_encode([
                'workspace_id' => $workspaceId,
                'payload'      => $payload,
            ]));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WebSocket broadcast failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function broadcastIssueUpdated(Task $task, string $workspaceId, string $actorId = '1'): void
    {
        self::broadcastToWorkspace($workspaceId, [
            'type'       => 'issue:updated',
            'payload'    => ['issue' => self::issuePayload($task)],
            'actor_id'   => $actorId,
            'actor_type' => 'user',
        ]);
    }

    public static function broadcastIssueCreated(Task $task, string $workspaceId, string $actorId = '1'): void
    {
        self::broadcastToWorkspace($workspaceId, [
            'type'       => 'issue:created',
            'payload'    => ['issue' => self::issuePayload($task)],
            'actor_id'   => $actorId,
            'actor_type' => 'user',
        ]);
    }

    public static function broadcastIssueDeleted(string $taskId, string $workspaceId, string $actorId = '1'): void
    {
        self::broadcastToWorkspace($workspaceId, [
            'type'       => 'issue:deleted',
            'payload'    => ['issue_id' => $taskId],
            'actor_id'   => $actorId,
            'actor_type' => 'user',
        ]);
    }

    public static function broadcastCommentCreated(TaskComment $comment, string $workspaceId): void
    {
        self::broadcastToWorkspace($workspaceId, [
            'type'       => 'comment:created',
            'payload'    => ['comment' => self::commentPayload($comment)],
            'actor_id'   => (string) $comment->user_id,
            'actor_type' => 'user',
        ]);
    }

    public static function wakeupDaemon(string $runtimeId, string $taskId): void
    {
        try {
            Redis::publish('pishift:ws:daemon', json_encode([
                'type'       => 'daemon:task_available',
                'runtime_id' => $runtimeId,
                'task_id'    => $taskId,
            ]));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Daemon wakeup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function issuePayload(Task $task): array
    {
        $task->loadMissing('project.teams');
        $workspaceId = $task->project?->teams?->first()?->id;
        return [
            'id'             => (string) $task->id,
            'workspace_id'   => $workspaceId ? (string) $workspaceId : '',
            'number'         => (int) $task->id,
            'identifier'     => 'task-' . $task->id,
            'title'          => $task->title,
            'description'    => $task->description ?? '',
            'status'         => str_replace('-', '_', $task->status),
            'priority'       => $task->priority,
            'assignee_type'  => $task->assigned_to ? 'user' : null,
            'assignee_id'    => $task->assigned_to ? (string) $task->assigned_to : null,
            'creator_type'   => 'user',
            'creator_id'     => '1',
            'project_id'     => (string) $task->project_id,
            'parent_issue_id'=> null,
            'position'       => 0.0,
            'start_date'     => null,
            'due_date'       => $task->due_date?->toDateString(),
            'created_at'     => $task->created_at->toIso8601String(),
            'updated_at'     => $task->updated_at->toIso8601String(),
            'metadata'       => (object) [],
            'labels'         => [],
        ];
    }

    private static function commentPayload(TaskComment $comment): array
    {
        return [
            'id'               => (string) $comment->id,
            'issue_id'         => (string) $comment->task_id,
            'author_type'      => 'user',
            'author_id'        => (string) $comment->user_id,
            'content'          => $comment->body,
            'type'             => 'comment',
            'parent_id'        => null,
            'created_at'       => $comment->created_at->toIso8601String(),
            'updated_at'       => $comment->updated_at->toIso8601String(),
            'resolved_at'      => null,
            'resolved_by_type' => null,
            'resolved_by_id'   => null,
            'reactions'        => [],
            'attachments'      => [],
        ];
    }
}
