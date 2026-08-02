<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueListController extends Controller
{
    public function __construct(private readonly IssuePayloadTransformer $issuePayloadTransformer = new IssuePayloadTransformer()) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'limit'  => ['nullable', 'integer', 'min:1', 'max:200'],
            'status' => ['nullable', 'string', 'max:100'],
            'sort'   => ['nullable', 'string', 'max:100'],
        ]);

        $query = Task::query()->with(['project.teams:id,lead_user_id', 'assignee:id']);
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $userId = $user->id;
            $query->whereHas('project.teams.members', function ($memberQuery) use ($userId): void {
                $memberQuery->where('users.id', $userId);
            });
        }

        if (! empty($data['status'])) {
            $mapped = $this->issuePayloadTransformer->normalizeIncomingStatus((string) $data['status']);

            if ($mapped !== null) {
                $query->where('status', $mapped);
            }
        }

        $this->applySort($query, $data['sort'] ?? null);

        if (! empty($data['limit'])) {
            $query->limit((int) $data['limit']);
        }

        $issues = $query->get();
        $fallbackUserId = $user?->id;

        return response()->json([
            'issues' => $issues->map(fn (Task $task): array => $this->issuePayloadTransformer->transform($task, $fallbackUserId))->values(),
            'total'  => $issues->count(),
        ]);
    }

    public function childProgress(): JsonResponse
    {
        return response()->json(['progress' => []]);
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
