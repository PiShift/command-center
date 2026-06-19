<?php

namespace App\Http\Controllers\Api;

use App\Models\AgentTaskQueue;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueActionController
{
    public function search(): JsonResponse
    {
        return response()->json(['issues' => [], 'total' => 0]);
    }

    public function grouped(): JsonResponse
    {
        return response()->json(['groups' => [], 'total' => 0]);
    }

    public function assigneeFrequency(): JsonResponse
    {
        return response()->json([]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'project_id' => ['nullable', 'integer'],
            'assignee_id' => ['nullable', 'string'],
            'assignee_type' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $projectId = null;

        if (! empty($data['project_id'])) {
            $project = Project::query()->whereKey((int) $data['project_id'])->first();
            $projectId = $project?->id;
        }

        if (! $projectId) {
            $projectId = Project::query()
                ->whereHas('teams.members', function ($q) use ($user): void {
                    $q->where('users.id', $user->id);
                })
                ->value('id');
        }

        if (! $projectId) {
            return response()->json(['error' => 'project_id is required'], 422);
        }

        $task = Task::create([
            'project_id' => $projectId,
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'type' => 'feature',
            'priority' => $data['priority'] ?? 'medium',
            'status' => $this->mapIncomingStatus((string) ($data['status'] ?? 'todo')),
            'assigned_to' => (isset($data['assignee_type'], $data['assignee_id']) && strtolower((string) $data['assignee_type']) === 'user' && is_numeric((string) $data['assignee_id']))
                ? (int) $data['assignee_id']
                : null,
        ]);

        return response()->json($this->issuePayload($task->fresh()), 201);
    }

    public function quickCreate(): JsonResponse
    {
        return response()->json(['task_id' => '']);
    }

    public function batchUpdate(): JsonResponse
    {
        return response()->json(['updated' => 0]);
    }

    public function batchDelete(): JsonResponse
    {
        return response()->json(['deleted' => 0]);
    }

    public function childrenBulk(): JsonResponse
    {
        return response()->json(['issues' => []]);
    }

    public function subscribe(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function unsubscribe(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    private function issuePayload(Task $task): array
    {
        $task->loadMissing(['project.teams:id', 'checklists']);
        $workspaceId = $task->project?->teams?->first()?->id;
        $assigneeId = $task->assigned_to ? (string) $task->assigned_to : null;

        return [
            'id'              => (string) $task->id,
            'workspace_id'    => $workspaceId ? (string) $workspaceId : '',
            'number'          => (int) $task->id,
            'identifier'      => 'task-' . $task->id,
            'title'           => (string) $task->title,
            'description'     => AgentTaskQueue::buildPrompt($task),
            'status'          => (string) $task->status,
            'priority'        => (string) $task->priority,
            'assignee_type'   => $assigneeId ? 'user' : null,
            'assignee_id'     => $assigneeId,
            'creator_type'    => 'user',
            'creator_id'      => $assigneeId,
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

    private function mapIncomingStatus(string $status): string
    {
        $incoming = strtolower(trim($status));

        $statusMap = [
            'todo' => 'open',
            'open' => 'open',
            'in_progress' => 'in-progress',
            'in-progress' => 'in-progress',
            'in_review' => 'in-review',
            'in-review' => 'in-review',
            'done' => 'done',
            'completed' => 'done',
            'backlog' => 'open',
            'blocked' => 'in-progress',
            'cancelled' => 'done',
        ];

        if (array_key_exists($incoming, $statusMap)) {
            return $statusMap[$incoming];
        }

        return KanbanColumn::query()->where('slug', $incoming)->exists() ? $incoming : 'open';
    }
}
