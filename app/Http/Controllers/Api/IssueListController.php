<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTaskQueue;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'limit'  => ['nullable', 'integer', 'min:1', 'max:200'],
            'status' => ['nullable', 'string', 'max:100'],
            'sort'   => ['nullable', 'string', 'max:100'],
        ]);

        $query = Task::query()->with(['project.teams:id', 'assignee:id']);
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $userId = $user->id;
            $query->whereHas('project.teams.members', function ($memberQuery) use ($userId): void {
                $memberQuery->where('users.id', $userId);
            });
        }

        if (! empty($data['status'])) {
            $mapped = $this->mapIncomingStatus((string) $data['status']);

            if ($mapped !== null) {
                $query->where('status', $mapped);
            }
        }

        $this->applySort($query, $data['sort'] ?? null);

        if (! empty($data['limit'])) {
            $query->limit((int) $data['limit']);
        }

        $issues = $query->get();

        return response()->json([
            'issues' => $issues->map(fn (Task $task): array => $this->issuePayload($task))->values(),
            'total'  => $issues->count(),
        ]);
    }

    public function childProgress(): JsonResponse
    {
        return response()->json(['progress' => []]);
    }

    private function issuePayload(Task $task): array
    {
        $workspaceId = $task->project?->teams?->first()?->id;
        $typedAssigneeId = null;
        $assigneeType = null;

        if (!empty($task->agent_id)) {
            $assigneeType = 'agent';
            $typedAssigneeId = 'agent-' . (string) $task->agent_id;
        } elseif (!empty($task->assigned_to)) {
            $assigneeType = 'user';
            $typedAssigneeId = 'user-' . (string) $task->assigned_to;
        }

        return [
            'id'              => (string) $task->id,
            'workspace_id'    => $workspaceId ? (string) $workspaceId : '',
            'number'          => (int) $task->id,
            'identifier'      => 'task-' . $task->id,
            'title'           => (string) $task->title,
            'description'     => AgentTaskQueue::buildPrompt($task),
            'status'          => $this->mapOutgoingStatus((string) $task->status),
            'priority'        => (string) $task->priority,
            'assignee_type'   => $assigneeType,
            'assignee_id'     => $typedAssigneeId,
            'creator_type'    => 'user',
            'creator_id'      => 'user-1',
            'parent_issue_id' => null,
            'project_id'      => (string) $task->project_id,
            'position'        => 0.0,
            'start_date'      => null,
            'due_date'        => optional($task->due_date)?->toDateString(),
            'created_at'      => optional($task->created_at)?->toIso8601String(),
            'updated_at'      => optional($task->updated_at)?->toIso8601String(),
            'metadata'        => (object) [],
            'labels'          => [],
        ];
    }

    private function mapIncomingStatus(string $status): ?string
    {
        $incoming = strtolower(trim($status));
        return str_replace('_', '-', $incoming);
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

    private function applySort($query, ?string $sort): void
    {
        $sortValue = trim((string) $sort);
        $direction = 'asc';

        if ($sortValue === '') {
            $query->orderByDesc('updated_at');
            return;
        }

        if (str_starts_with($sortValue, '-')) {
            $direction = 'desc';
            $sortValue = substr($sortValue, 1);
        }

        $allowed = ['id', 'created_at', 'updated_at', 'due_date', 'priority', 'status'];

        if (! in_array($sortValue, $allowed, true)) {
            $query->orderByDesc('updated_at');
            return;
        }

        $query->orderBy($sortValue, $direction);
    }
}
