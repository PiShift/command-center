<?php

namespace App\Http\Controllers\Api;

use App\Models\AgentTaskQueue;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IssueActionController
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['issues' => [], 'total' => 0]);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 20)));
        $offset = max(0, (int) $request->query('offset', 0));
        $includeClosed = filter_var($request->query('include_closed', false), FILTER_VALIDATE_BOOLEAN);

        $query = Task::query()->with(['project.teams:id', 'assignee:id']);
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $userId = $user->id;
            $query->whereHas('project.teams.members', function ($memberQuery) use ($userId): void {
                $memberQuery->where('users.id', $userId);
            });
        }

        $query->where(function ($issueQuery) use ($q): void {
            $issueQuery
                ->where('title', 'like', '%' . $q . '%')
                ->orWhere('description', 'like', '%' . $q . '%');
        });

        if (! $includeClosed) {
            $query->where('status', '!=', 'done');
        }

        $total = (clone $query)->count();
        $issues = $query
            ->orderByDesc('updated_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'issues' => $issues->map(fn (Task $task): array => $this->issuePayload($task))->values(),
            'total'  => $total,
        ]);
    }

    public function grouped(Request $request): JsonResponse
    {
        $groupBy = trim((string) $request->query('group_by', 'status'));
        $allowed = ['status', 'priority', 'project_id', 'assignee_id'];

        if (! in_array($groupBy, $allowed, true)) {
            $groupBy = 'status';
        }

        $column = match ($groupBy) {
            'assignee_id' => 'assigned_to',
            default => $groupBy,
        };

        $query = Task::query()->with(['project.teams:id', 'assignee:id']);
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $userId = $user->id;
            $query->whereHas('project.teams.members', function ($memberQuery) use ($userId): void {
                $memberQuery->where('users.id', $userId);
            });
        }

        $issues = $query->orderByDesc('updated_at')->get();
        $grouped = $issues->groupBy(function (Task $task) use ($column, $groupBy): string {
            $raw = $task->{$column};

            if ($groupBy === 'status') {
                return $this->mapOutgoingStatus((string) $raw);
            }

            if ($raw === null || $raw === '') {
                return 'null';
            }

            return (string) $raw;
        });

        $groups = $grouped
            ->map(function (Collection $items, string $key): array {
                return [
                    'key'    => $key,
                    'issues' => $items->map(fn (Task $task): array => $this->issuePayload($task))->values(),
                    'total'  => $items->count(),
                ];
            })
            ->values();

        return response()->json([
            'groups' => $groups,
            'total'  => $issues->count(),
        ]);
    }

    public function assigneeFrequency(Request $request): JsonResponse
    {
        $query = Task::query();
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $userId = $user->id;
            $query->whereHas('project.teams.members', function ($memberQuery) use ($userId): void {
                $memberQuery->where('users.id', $userId);
            });
        }

        $frequencies = $query
            ->select('assigned_to', DB::raw('COUNT(*) as count'))
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json(
            $frequencies->map(static function ($row): array {
                return [
                    'assignee_type' => 'user',
                    'assignee_id'   => (string) $row->assigned_to,
                    'count'         => (int) $row->count,
                ];
            })->values()
        );
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
            'status'          => $this->mapOutgoingStatus((string) $task->status),
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

    private function mapOutgoingStatus(string $status): string
    {
        return match ($status) {
            'open' => 'todo',
            'in-progress' => 'in_progress',
            'in-review' => 'in_review',
            default => $status,
        };
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
