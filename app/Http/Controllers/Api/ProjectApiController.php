<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasPermission('projects.view')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $projects = $this->scopedProjects($request)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'projects' => $projects->map(fn (Project $project): array => $this->projectSummaryPayload($project))->values(),
            'total' => $projects->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('projects.create')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $data = $request->validate([
            'title'                      => ['required', 'string', 'max:255'],
            'description'                => ['nullable', 'string'],
            'status'                     => ['nullable', 'in:active,paused,complete'],
            'health'                     => ['nullable', 'in:on-track,at-risk,blocked'],
            'priority'                   => ['nullable', 'string', 'max:50'],
            'resources'                  => ['nullable', 'array'],
            'resources.*.resource_type'  => ['required_with:resources', 'in:github_repo,local_directory'],
            'resources.*.resource_ref'   => ['required_with:resources', 'array'],
            'resources.*.label'          => ['nullable', 'string', 'max:255'],
            'resources.*.position'       => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        $project = DB::transaction(function () use ($data, $user) {
            $project = Project::create([
                'name'        => trim($data['title']),
                'description' => $data['description'] ?? null,
                'status'      => $data['status'] ?? 'active',
                'health'      => $data['health'] ?? 'on-track',
            ]);

            $userTeamIds = $user->teams()->pluck('teams.id');
            if ($userTeamIds->isNotEmpty()) {
                $project->teams()->syncWithoutDetaching($userTeamIds);
            }

            foreach (($data['resources'] ?? []) as $index => $resourceData) {
                $normalizedRef = $this->normalizeResourceRef(
                    $project,
                    (string) $resourceData['resource_type'],
                    (array) $resourceData['resource_ref']
                );

                ProjectResource::create([
                    'project_id'     => $project->id,
                    'resource_type'  => $resourceData['resource_type'],
                    'resource_ref'   => $normalizedRef,
                    'label'          => $resourceData['label'] ?? null,
                    'position'       => (int) ($resourceData['position'] ?? $index),
                    'created_by'     => $user->id,
                ]);
            }

            return $project;
        });

        $project->load(['resources', 'teams']);

        return response()->json([
            'project' => $this->projectDetailPayload($project),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        if (! $request->user()->hasPermission('projects.view')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $project = $this->scopedProjects($request)
            ->with(['resources', 'teams'])
            ->where('id', (int) $id)
            ->first();

        if (! $project) {
            return response()->json(['error' => 'not found'], 404);
        }

        return response()->json($this->projectDetailPayload($project));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (! $request->user()->hasPermission('projects.edit')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $project = $this->scopedProjects($request)
            ->with(['resources', 'teams'])
            ->where('id', (int) $id)
            ->first();

        if (! $project) {
            return response()->json(['error' => 'not found'], 404);
        }

        $data = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'in:active,paused,complete'],
            'health'      => ['sometimes', 'in:on-track,at-risk,blocked'],
            'priority'    => ['nullable', 'string', 'max:50'],
        ]);

        $updates = [];

        if (array_key_exists('title', $data)) {
            $updates['name'] = trim((string) $data['title']);
        }

        if (array_key_exists('description', $data)) {
            $updates['description'] = $data['description'];
        }

        if (array_key_exists('status', $data)) {
            $updates['status'] = $data['status'];
        }

        if (array_key_exists('health', $data)) {
            $updates['health'] = $data['health'];
        }

        if (! empty($updates)) {
            $project->update($updates);
        }

        return response()->json([
            'project' => $this->projectDetailPayload($project->fresh(['resources', 'teams'])),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! $request->user()->hasPermission('projects.manage')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $project = $this->scopedProjects($request)
            ->where('id', (int) $id)
            ->first();

        if (! $project) {
            return response()->json(['error' => 'not found'], 404);
        }

        Project::query()->whereKey($project->id)->delete();

        return response()->json(['status' => 'ok']);
    }

    private function scopedProjects(Request $request)
    {
        $query = Project::query();
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $query->whereHas('teams', function ($teamQuery) use ($user): void {
                $teamQuery->whereHas('members', function ($memberQuery) use ($user): void {
                    $memberQuery->where('users.id', $user->id);
                });
            });
        }

        return $query;
    }

    private function projectSummaryPayload(Project $project): array
    {
        $workspaceId = (string) ($project->teams()->first()?->id ?? '1');

        return [
            'id'             => (string) $project->id,
            'workspace_id'   => $workspaceId,
            'title'          => $project->name,
            'description'    => $project->description,
            'icon'           => null,
            'status'         => $this->mapOutgoingProjectStatus((string) $project->status),
            'priority'       => 'medium',
            'lead_type'      => null,
            'lead_id'        => null,
            'created_at'     => optional($project->created_at)?->toIso8601String(),
            'updated_at'     => optional($project->updated_at)?->toIso8601String(),
            'issue_count'    => (int) $project->tasks()->count(),
            'done_count'     => (int) $project->tasks()->where('status', 'done')->count(),
            'resource_count' => (int) $project->resources()->count(),
        ];
    }

    private function projectDetailPayload(Project $project): array
    {
        return array_merge(
            $this->projectSummaryPayload($project),
            [
                'resources' => $project->resources->map(static function (ProjectResource $resource): array {
                    return [
                        'id'            => (string) $resource->id,
                        'project_id'    => (string) $resource->project_id,
                        'resource_type' => $resource->resource_type,
                        'resource_ref'  => $resource->resource_ref ?? [],
                        'label'         => $resource->label,
                        'position'      => $resource->position,
                        'created_at'    => optional($resource->created_at)?->toIso8601String(),
                        'updated_at'    => optional($resource->updated_at)?->toIso8601String(),
                    ];
                })->values(),
            ]
        );
    }

    private function mapOutgoingProjectStatus(string $status): string
    {
        return match ($status) {
            'active' => 'in_progress',
            'paused' => 'paused',
            'complete' => 'completed',
            default => 'planned',
        };
    }

    private function normalizeResourceRef(Project $project, string $resourceType, array $resourceRef): array
    {
        if ($resourceType === 'github_repo') {
            $url = trim((string) ($resourceRef['url'] ?? ''));
            $url = rtrim($url, '/');

            if (! $this->isValidGitUrl($url)) {
                abort(response()->json(['error' => 'Invalid git URL format for github_repo'], 422));
            }

            return ['url' => $url];
        }

        $path = trim((string) ($resourceRef['local_path'] ?? ''));
        $daemonId = trim((string) ($resourceRef['daemon_id'] ?? ''));

        if ($path === '') {
            abort(response()->json(['error' => 'resource_ref.local_path is required'], 422));
        }

        if ($daemonId === '') {
            abort(response()->json(['error' => 'resource_ref.daemon_id is required'], 422));
        }

        $runtimeExists = \App\Models\AgentRuntime::query()->where('daemon_id', $daemonId)->exists();
        if (! $runtimeExists) {
            abort(response()->json(['error' => 'resource_ref.daemon_id not found'], 422));
        }

        $conflictExists = ProjectResource::query()
            ->where('project_id', $project->id)
            ->where('resource_type', 'local_directory')
            ->whereRaw("resource_ref->>'daemon_id' = ?", [$daemonId])
            ->exists();

        if ($conflictExists) {
            abort(response()->json([
                'error' => 'A local directory is already configured for this daemon on this project',
            ], 409));
        }

        $normalized = [
            'local_path' => $path,
            'daemon_id'  => $daemonId,
        ];

        if (isset($resourceRef['label']) && trim((string) $resourceRef['label']) !== '') {
            $normalized['label'] = trim((string) $resourceRef['label']);
        }

        return $normalized;
    }

    private function isValidGitUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (preg_match('/^[\w.-]+@[\w.-]+:[\w.\/-]+(\.git)?$/', $url) === 1) {
            return true;
        }

        if (str_starts_with($url, 'ssh://') || str_starts_with($url, 'git://') || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        return false;
    }
}
