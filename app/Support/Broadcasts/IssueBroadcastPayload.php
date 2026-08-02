<?php

namespace App\Support\Broadcasts;

use App\Models\Task;

trait IssueBroadcastPayload
{
    private function resolveWorkspaceId(Task $task): string
    {
        $task->loadMissing('project.teams');

        return (string) ($task->project?->teams?->first()?->id ?? '');
    }

    private function issuePayload(Task $task): array
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
}