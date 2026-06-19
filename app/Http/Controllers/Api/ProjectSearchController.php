<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectSearchController
{
    public function search(Request $request): JsonResponse
    {
        $queryText = trim((string) $request->query('q', ''));

        if ($queryText === '') {
            return response()->json(['projects' => [], 'total' => 0]);
        }

        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $offset = max(0, (int) $request->query('offset', 0));
        $includeClosed = filter_var($request->query('include_closed', false), FILTER_VALIDATE_BOOLEAN);

        $user = $request->user();
        $query = Project::query()
            ->with('teams')
            ->withCount([
                'tasks',
                'resources',
                'tasks as done_tasks_count' => fn ($taskQuery) => $taskQuery->where('status', 'done'),
            ])
            ->where(function ($projectQuery) use ($queryText): void {
                $projectQuery
                    ->where('name', 'like', '%' . $queryText . '%')
                    ->orWhere('description', 'like', '%' . $queryText . '%');
            })
            ->orderBy('name', 'asc');

        if (! $includeClosed) {
            $query->where('status', '!=', 'complete');
        }

        if (! $user->hasPermission('projects.view_all')) {
            $query->whereHas('teams', function ($teamQuery) use ($user): void {
                $teamQuery->whereHas('members', function ($memberQuery) use ($user): void {
                    $memberQuery->where('users.id', $user->id);
                });
            });
        }

        $total = (clone $query)->count();
        $projects = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'projects' => $projects->map(function (Project $project): array {
                $statusMap = [
                    'active'   => 'in_progress',
                    'paused'   => 'paused',
                    'complete' => 'completed',
                ];

                return [
                    'id'             => (string) $project->id,
                    'workspace_id'   => (string) ($project->teams()->first()?->id ?? '1'),
                    'title'          => $project->name,
                    'description'    => $project->description,
                    'icon'           => null,
                    'status'         => $statusMap[$project->status] ?? 'planned',
                    'priority'       => 'medium',
                    'lead_type'      => null,
                    'lead_id'        => null,
                    'created_at'     => optional($project->created_at)?->toIso8601String(),
                    'updated_at'     => optional($project->updated_at)?->toIso8601String(),
                    'issue_count'    => (int) $project->tasks_count,
                    'done_count'     => (int) $project->done_tasks_count,
                    'resource_count' => (int) $project->resources_count,
                ];
            })->values(),
            'total' => $total,
        ]);
    }
}
