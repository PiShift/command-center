<?php

namespace App\WebSocket;

class WebSocketBroadcaster
{
    /**
     * Broadcast task/issue updated event
     */
    public static function broadcastIssueUpdated(
        \App\Models\Task $task,
        string $workspaceId,
        string $actorId = '1'
    ): void {
        $server = app(PiShiftWebSocketServer::class, [], true);
        if (!$server) return;

        $server->broadcastToWorkspace($workspaceId, [
            'type'       => 'issue:updated',
            'payload'    => ['issue' => self::issuePayload($task)],
            'actor_id'   => $actorId,
            'actor_type' => 'user',
        ]);
    }

    /**
     * Broadcast comment created event
     */
    public static function broadcastCommentCreated(
        \App\Models\TaskComment $comment,
        string $workspaceId
    ): void {
        $server = app(PiShiftWebSocketServer::class, [], true);
        if (!$server) return;

        $server->broadcastToWorkspace($workspaceId, [
            'type'       => 'comment:created',
            'payload'    => ['comment' => self::commentPayload($comment)],
            'actor_id'   => (string) $comment->user_id,
            'actor_type' => 'user',
        ]);
    }

    /**
     * Wake up daemon for available task
     */
    public static function wakeupDaemon(string $runtimeId, string $taskId): void
    {
        $server = app(PiShiftWebSocketServer::class, [], true);
        if (!$server) return;

        $server->wakeupDaemon($runtimeId, $taskId);
    }

    /**
     * Format task/issue payload for Multica protocol
     */
    private static function issuePayload(\App\Models\Task $task): array
    {
        $task->loadMissing(['project.teams:id']);
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

    /**
     * Format comment payload for Multica protocol
     */
    private static function commentPayload(\App\Models\TaskComment $comment): array
    {
        return [
            'id'              => (string) $comment->id,
            'issue_id'        => (string) $comment->task_id,
            'author_type'     => 'user',
            'author_id'       => (string) $comment->user_id,
            'content'         => $comment->body,
            'type'            => 'comment',
            'parent_id'       => null,
            'created_at'      => $comment->created_at->toIso8601String(),
            'updated_at'      => $comment->updated_at->toIso8601String(),
            'resolved_at'     => null,
            'resolved_by_type'=> null,
            'resolved_by_id'  => null,
            'reactions'       => [],
            'attachments'     => [],
        ];
    }

    public static function broadcastToWorkspace(string $workspaceId, array $payload): void
    {
        try {
            $server = app(\App\WebSocket\PiShiftWebSocketServer::class);
            if (!$server) return;
            $server->broadcastToWorkspace($workspaceId, $payload);
        } catch (\Throwable) {
            // WebSocket server may not be running
        }
    }
}
