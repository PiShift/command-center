<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\AgentTaskQueue;
use App\Models\Project;
use App\Models\Task;
use App\WebSocket\WebSocketBroadcaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IssueActionController
{
    public function __construct(private readonly IssuePayloadTransformer $issuePayloadTransformer = new IssuePayloadTransformer) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['issues' => [], 'total' => 0]);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 20)));
        $offset = max(0, (int) $request->query('offset', 0));
        $includeClosed = filter_var($request->query('include_closed', false), FILTER_VALIDATE_BOOLEAN);

        $query = Task::query()->with(['project.teams:id,lead_user_id', 'assignee:id']);
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $userId = $user->id;
            $query->whereHas('project.teams.members', function ($memberQuery) use ($userId): void {
                $memberQuery->where('users.id', $userId);
            });
        }

        $query->where(function ($issueQuery) use ($q): void {
            $issueQuery
                ->where('title', 'like', '%'.$q.'%')
                ->orWhere('description', 'like', '%'.$q.'%');
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
        $fallbackUserId = $user?->id;

        return response()->json([
            'issues' => $issues->map(fn (Task $task): array => $this->issuePayloadTransformer->transform($task, $fallbackUserId))->values(),
            'total' => $total,
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

        $query = Task::query()->with(['project.teams:id,lead_user_id', 'assignee:id']);
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
                return $this->issuePayloadTransformer->normalizeOutgoingStatus((string) $raw);
            }

            if ($groupBy === 'assignee_id') {
                [, $assigneeId] = $this->issuePayloadTransformer->normalizeIncomingAssignee('member', $raw === null ? null : (string) $raw);

                return $assigneeId ? 'member-'.$assigneeId : 'null';
            }

            if ($raw === null || $raw === '') {
                return 'null';
            }

            return (string) $raw;
        });

        $fallbackUserId = $user?->id;

        $groups = $grouped
            ->map(function (Collection $items, string $key) use ($fallbackUserId): array {
                return [
                    'key' => $key,
                    'issues' => $items->map(fn (Task $task): array => $this->issuePayloadTransformer->transform($task, $fallbackUserId))->values(),
                    'total' => $items->count(),
                ];
            })
            ->values();

        return response()->json([
            'groups' => $groups,
            'total' => $issues->count(),
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
                    'assignee_type' => 'member',
                    'assignee_id' => 'member-'.(string) $row->assigned_to,
                    'count' => (int) $row->count,
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
            'status' => $this->issuePayloadTransformer->normalizeIncomingStatus((string) ($data['status'] ?? 'backlog')) ?? 'open',
        ]);

        if (array_key_exists('assignee_id', $data) || array_key_exists('assignee_type', $data)) {
            $this->issuePayloadTransformer->applyIncomingAssignee(
                $task,
                $data['assignee_type'] ?? null,
                $data['assignee_id'] ?? null,
            );

            if ($task->agent_id && ! $task->assigned_to) {
                $agent = Agent::find($task->agent_id);
                if ($agent) {
                    $task->assigned_to = $agent->owner_id;
                }
            }

            $task->save();

            // If agent assigned, create queue entry and wake up daemon
            if ($task->agent_id) {
                $agent = Agent::where('id', $task->agent_id)
                    ->whereNull('archived_at')
                    ->first();

                if ($agent) {
                    $task->loadMissing('checklists');
                    $queueEntry = AgentTaskQueue::create([
                        'task_id' => $task->id,
                        'agent_id' => $agent->id,
                        'runtime_id' => $agent->runtime_id,
                        'team_id' => $agent->team_id,
                        'status' => 'queued',
                        'prompt' => AgentTaskQueue::buildPrompt($task),
                    ]);
                    WebSocketBroadcaster::wakeupDaemon($agent->runtime_id, $queueEntry->id);
                }
            }
        }

        return response()->json($this->issuePayloadTransformer->transform($task->fresh()), 201);
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
}
