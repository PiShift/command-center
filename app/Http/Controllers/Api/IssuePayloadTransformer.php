<?php

namespace App\Http\Controllers\Api;

use App\Models\KanbanColumn;
use App\Models\Task;

class IssuePayloadTransformer
{
    public function transform(Task $task): array
    {
        $task->loadMissing(['project.teams:id,lead_user_id', 'assignee:id', 'agent:id']);

        $workspaceId = $task->project?->teams?->sortBy('id')->first()?->id;
        [$assigneeType, $assigneeId] = $this->outgoingAssignee($task);
        [$creatorType, $creatorId] = $this->creatorFallback();

        return [
            'id'              => (string) $task->id,
            'workspace_id'    => $workspaceId ? (string) $workspaceId : '',
            'number'          => (int) $task->id,
            'identifier'      => 'task-' . $task->id,
            'title'           => (string) $task->title,
            'description'     => (string) ($task->description ?? ''),
            'status'          => $this->normalizeOutgoingStatus((string) $task->status),
            'priority'        => (string) $task->priority,
            'assignee_type'   => $assigneeType,
            'assignee_id'     => $assigneeId,
            'creator_type'    => $creatorType,
            'creator_id'      => $creatorId,
            'parent_issue_id' => null,
            'project_id'      => (string) $task->project_id,
            'position'        => 0.0,
            'start_date'      => null,
            'due_date'        => optional($task->due_date)?->toDateString(),
            'created_at'      => optional($task->created_at)?->toIso8601String(),
            'updated_at'      => optional($task->updated_at)?->toIso8601String(),
            'metadata'        => (object) [],
            'reactions'       => [],
            'attachments'     => [],
            'labels'          => [],
        ];
    }

    public function normalizeIncomingStatus(?string $status): ?string
    {
        $incoming = strtolower(trim((string) $status));

        if ($incoming === '') {
            return null;
        }

        $statusMap = [
            'todo'        => 'todo',
            'open'        => 'open',
            'backlog'     => 'open',
            'in_progress' => 'in-progress',
            'in-progress' => 'in-progress',
            'blocked'     => 'blocked',
            'in_review'   => 'in-review',
            'in-review'   => 'in-review',
            'done'        => 'done',
            'completed'   => 'done',
            'cancelled'   => 'cancelled',
        ];

        if (array_key_exists($incoming, $statusMap)) {
            return $statusMap[$incoming];
        }

        $slug = str_replace('_', '-', $incoming);

        return KanbanColumn::query()->where('slug', $slug)->exists() ? $slug : null;
    }

    public function normalizeOutgoingStatus(string $status): string
    {
        return match ($status) {
            'open' => 'backlog',
            'in-progress' => 'in_progress',
            'in-review' => 'in_review',
            default => str_replace('-', '_', $status),
        };
    }

    public function normalizeIncomingAssignee(?string $assigneeType, ?string $assigneeId): array
    {
        $rawType = strtolower(trim((string) $assigneeType));
        $rawId = trim((string) $assigneeId);

        if ($rawType === '' && $rawId === '') {
            return ['type' => null, 'id' => null];
        }

        if ($rawId === '' || $rawType === '') {
            return ['type' => null, 'id' => null];
        }

        foreach (['member', 'agent', 'squad'] as $prefixType) {
            $prefix = $prefixType . '-';

            if (str_starts_with($rawId, $prefix)) {
                $normalizedId = substr($rawId, strlen($prefix));

                return [
                    'type' => $prefixType,
                    'id'   => $normalizedId !== '' ? $normalizedId : null,
                ];
            }
        }

        $normalizedType = match ($rawType) {
            'user', 'member' => 'member',
            'agent' => 'agent',
            'squad' => 'squad',
            default => null,
        };

        if ($normalizedType === null) {
            return ['type' => null, 'id' => null];
        }

        return [
            'type' => $normalizedType,
            'id'   => $rawId !== '' ? $rawId : null,
        ];
    }

    public function applyIncomingAssignee(Task $task, ?string $assigneeType, ?string $assigneeId): void
    {
        $normalized = $this->normalizeIncomingAssignee($assigneeType, $assigneeId);

        if ($normalized['type'] === null || $normalized['id'] === null) {
            $task->assigned_to = null;
            $task->agent_id = null;

            return;
        }

        if ($normalized['type'] === 'member' && ctype_digit((string) $normalized['id'])) {
            $task->assigned_to = (int) $normalized['id'];
            $task->agent_id = null;

            return;
        }

        if ($normalized['type'] === 'agent') {
            $task->agent_id = (string) $normalized['id'];
            $task->assigned_to = null;

            return;
        }

        $task->assigned_to = null;
        $task->agent_id = null;
    }

    private function outgoingAssignee(Task $task): array
    {
        if (! empty($task->agent_id)) {
            return ['agent', 'agent-' . (string) $task->agent_id];
        }

        if (! empty($task->assigned_to)) {
            return ['member', 'member-' . (string) $task->assigned_to];
        }

        return [null, null];
    }

    private function creatorFallback(): array
    {
        // Tasks do not currently persist a creator reference, so the API exposes
        // a stable null creator contract until a dedicated creator column exists.
        return [null, null];
    }
}