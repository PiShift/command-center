<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentRuntime;
use App\Models\Project;
use App\Models\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectResourceController extends Controller
{
    public function index(Request $request, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $id);

        if (! $project) {
            return response()->json(['error' => 'not found'], 404);
        }

        if (! $request->user()->hasPermission('projects.view')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $resources = $project->resources()->get();

        return response()->json([
            'resources' => $resources->map(fn (ProjectResource $resource): array => $this->resourcePayload($resource))->values(),
            'total'     => $resources->count(),
        ]);
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $id);

        if (! $project) {
            return response()->json(['error' => 'not found'], 404);
        }

        if (! $request->user()->hasPermission('projects.edit')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $data = $request->validate([
            'resource_type'         => ['required', 'in:github_repo,local_directory'],
            'resource_ref'          => ['required', 'array'],
            'label'                 => ['nullable', 'string', 'max:255'],
            'position'              => ['nullable', 'integer'],
            'resource_ref.url'      => ['nullable', 'string', 'max:2048'],
            'resource_ref.local_path' => ['nullable', 'string'],
            'resource_ref.daemon_id'  => ['nullable', 'uuid'],
        ]);

        $normalizedRef = $this->normalizeResourceRef($project, $data['resource_type'], (array) $data['resource_ref']);

        $resource = ProjectResource::create([
            'project_id'    => $project->id,
            'resource_type' => $data['resource_type'],
            'resource_ref'  => $normalizedRef,
            'label'         => $data['label'] ?? null,
            'position'      => (int) ($data['position'] ?? 0),
            'created_by'    => $request->user()->id,
        ]);

        return response()->json($this->resourcePayload($resource), 201);
    }

    public function update(Request $request, string $id, string $resourceId): JsonResponse
    {
        $project = $this->resolveProject($request, $id);

        if (! $project) {
            return response()->json(['error' => 'not found'], 404);
        }

        if (! $request->user()->hasPermission('projects.edit')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $resource = ProjectResource::query()
            ->where('project_id', $project->id)
            ->where('id', $resourceId)
            ->first();

        if (! $resource) {
            return response()->json(['error' => 'not found'], 404);
        }

        $data = $request->validate([
            'label'                   => ['sometimes', 'nullable', 'string', 'max:255'],
            'position'                => ['sometimes', 'integer'],
            'resource_ref'            => ['sometimes', 'array'],
            'resource_ref.url'        => ['nullable', 'string', 'max:2048'],
            'resource_ref.local_path' => ['nullable', 'string'],
            'resource_ref.label'      => ['nullable', 'string', 'max:255'],
            'resource_ref.daemon_id'  => ['nullable', 'uuid'],
        ]);

        $updates = [];

        if (array_key_exists('label', $data)) {
            $updates['label'] = $data['label'];
        }

        if (array_key_exists('position', $data)) {
            $updates['position'] = (int) $data['position'];
        }

        if (array_key_exists('resource_ref', $data)) {
            $currentRef = is_array($resource->resource_ref) ? $resource->resource_ref : [];
            $incomingRef = (array) $data['resource_ref'];

            if ($resource->resource_type === 'local_directory') {
                $currentDaemonId = (string) ($currentRef['daemon_id'] ?? '');
                $newDaemonId = array_key_exists('daemon_id', $incomingRef)
                    ? trim((string) $incomingRef['daemon_id'])
                    : $currentDaemonId;

                if ($newDaemonId !== $currentDaemonId) {
                    return response()->json(['error' => 'daemon_id is immutable for local_directory resources'], 422);
                }
            }

            $mergedRef = array_merge($currentRef, $incomingRef);
            $updates['resource_ref'] = $this->normalizeResourceRef($project, $resource->resource_type, $mergedRef, $resource->id);
        }

        if (! empty($updates)) {
            $resource->update($updates);
        }

        return response()->json($this->resourcePayload($resource->fresh()));
    }

    public function destroy(Request $request, string $id, string $resourceId): JsonResponse
    {
        $project = $this->resolveProject($request, $id);

        if (! $project) {
            return response()->json(['error' => 'not found'], 404);
        }

        if (! $request->user()->hasPermission('projects.edit')) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $resource = ProjectResource::query()
            ->where('project_id', $project->id)
            ->where('id', $resourceId)
            ->first();

        if (! $resource) {
            return response()->json(['error' => 'not found'], 404);
        }

        ProjectResource::query()->whereKey($resource->id)->delete();

        return response()->json(['status' => 'ok']);
    }

    private function resolveProject(Request $request, string $id): ?Project
    {
        $query = Project::query()->where('id', (int) $id);
        $user = $request->user();

        if (! $user->hasPermission('projects.view_all')) {
            $query->whereHas('teams', function ($teamQuery) use ($user): void {
                $teamQuery->whereHas('members', function ($memberQuery) use ($user): void {
                    $memberQuery->where('users.id', $user->id);
                });
            });
        }

        return $query->first();
    }

    private function resourcePayload(ProjectResource $resource): array
    {
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
    }

    private function normalizeResourceRef(Project $project, string $resourceType, array $resourceRef, ?string $ignoreResourceId = null): array
    {
        if ($resourceType === 'github_repo') {
            $url = trim((string) ($resourceRef['url'] ?? ''));
            $url = rtrim($url, '/');

            if (! $this->isValidGitUrl($url)) {
                abort(response()->json(['error' => 'resource_ref.url must be a valid git URL'], 422));
            }

            return ['url' => $url];
        }

        $localPath = trim((string) ($resourceRef['local_path'] ?? ''));
        $daemonId = trim((string) ($resourceRef['daemon_id'] ?? ''));

        if ($localPath === '') {
            abort(response()->json(['error' => 'resource_ref.local_path is required'], 422));
        }

        if ($daemonId === '') {
            abort(response()->json(['error' => 'resource_ref.daemon_id is required'], 422));
        }

        $runtimeExists = AgentRuntime::query()->where('daemon_id', $daemonId)->exists();
        if (! $runtimeExists) {
            abort(response()->json(['error' => 'resource_ref.daemon_id must exist in agent_runtimes'], 422));
        }

        $conflictQuery = ProjectResource::query()
            ->where('project_id', $project->id)
            ->where('resource_type', 'local_directory')
            ->whereRaw("resource_ref->>'daemon_id' = ?", [$daemonId]);

        if ($ignoreResourceId !== null) {
            $conflictQuery->where('id', '!=', $ignoreResourceId);
        }

        if ($conflictQuery->exists()) {
            abort(response()->json([
                'error' => 'A local directory is already configured for this daemon on this project',
            ], 409));
        }

        $normalized = [
            'local_path' => $localPath,
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
