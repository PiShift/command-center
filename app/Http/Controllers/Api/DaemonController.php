<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTaskMessage;
use App\Models\AgentRuntime;
use App\Models\AgentTaskQueue;
use App\Models\ProjectResource;
use App\Models\TaskToken;
use App\Models\Team;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DaemonController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workspace_id'          => ['nullable', 'string'],
            'daemon_id'             => ['required', 'string', 'max:255'],
            'device_name'           => ['nullable', 'string', 'max:255'],
            'cli_version'           => ['nullable', 'string', 'max:255'],
            'launched_by'           => ['nullable', 'string', 'max:255'],
            'legacy_daemon_ids'     => ['nullable', 'array'],
            'runtimes'              => ['required', 'array', 'min:1'],
            'runtimes.*.name'       => ['required', 'string', 'max:255'],
            'runtimes.*.type'       => ['required', 'string', 'max:255'],
            'runtimes.*.version'    => ['nullable', 'string', 'max:255'],
            'runtimes.*.status'     => ['nullable', 'string', 'max:20'],
            'runtimes.*.profile_id' => ['nullable', 'string', 'max:255'],
        ]);

        $teamId = (int) ($data['workspace_id'] ?? 0);
        $team = Team::query()->whereKey($teamId)->first();

        if (! $team) {
            return response()->json(['error' => 'workspace not found'], 404);
        }

        if (! $this->isTeamMember($request->user(), $team)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $deviceName = trim((string) ($data['device_name'] ?? ''));
        $cliVersion = trim((string) ($data['cli_version'] ?? ''));
        $launchedBy = trim((string) ($data['launched_by'] ?? ''));
        $daemonId = (string) $data['daemon_id'];
        $userId = (int) $request->user()->id;
        $now = now();
        $responseRuntimes = [];

        foreach ($data['runtimes'] as $runtime) {
            $provider = strtolower(trim((string) $runtime['type']));
            $version = trim((string) ($runtime['version'] ?? ''));
            $status = strtolower((string) ($runtime['status'] ?? 'online'));

            if (! in_array($status, ['online', 'offline'], true)) {
                $status = 'online';
            }

            $agentRuntime = AgentRuntime::query()->updateOrCreate(
                [
                    'team_id'   => $team->id,
                    'daemon_id' => $daemonId,
                    'provider'  => $provider,
                ],
                [
                    'user_id'      => $userId,
                    'name'         => trim((string) $runtime['name']),
                    'status'       => $status,
                    'device_info'  => $this->buildDeviceInfo($deviceName, $version),
                    'cli_version'  => $cliVersion !== '' ? $cliVersion : null,
                    'launched_by'  => $launchedBy !== '' ? $launchedBy : null,
                    'last_seen_at' => $now,
                    'metadata'     => [
                        'version'      => $version !== '' ? $version : null,
                        'cli_version'  => $cliVersion !== '' ? $cliVersion : null,
                        'launched_by'  => $launchedBy !== '' ? $launchedBy : null,
                    ],
                ]
            );

            $responseRuntimes[] = [
                'id'          => $agentRuntime->id,
                'name'        => $agentRuntime->name,
                'provider'    => $agentRuntime->provider,
                'status'      => $agentRuntime->status,
                'device_info' => $agentRuntime->device_info,
            ];
        }

        $repos = $this->teamReposPayload($team);

        return response()->json([
            'runtimes'      => $responseRuntimes,
            'repos'         => $repos,
            'repos_version' => $this->reposVersion($repos),
            'settings'      => (object) [],
        ]);
    }

    public function deregister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'runtime_ids'   => ['required', 'array'],
            'runtime_ids.*' => ['string'],
        ]);

        AgentRuntime::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $data['runtime_ids'])
            ->update([
                'status'       => 'offline',
                'last_seen_at' => now(),
            ]);

        return response()->json(['status' => 'ok']);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'runtime_id' => ['required', 'string'],
        ]);

        $runtime = AgentRuntime::query()
            ->where('id', $data['runtime_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $runtime) {
            return response()->json(['error' => 'runtime not found', 'runtime_gone' => true], 404);
        }

        $runtime->forceFill([
            'last_seen_at' => now(),
            'status'       => 'online',
        ])->save();

        return response()->json([
            'status'     => 'ok',
            'runtime_id' => $runtime->id,
        ]);
    }

    public function workspaceRepos(Request $request, string $workspaceId): JsonResponse
    {
        $teamId = (int) $workspaceId;
        $team = Team::query()->whereKey($teamId)->first();

        if (! $team) {
            return response()->json(['error' => 'workspace not found'], 404);
        }

        if (! $this->isTeamMember($request->user(), $team)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $repos = $this->teamReposPayload($team);

        return response()->json([
            'workspace_id'  => (string) $team->id,
            'repos'         => $repos,
            'repos_version' => $this->reposVersion($repos),
            'settings'      => (object) [],
        ]);
    }

    public function claimTask(Request $request, string $runtimeId): JsonResponse
    {
        $runtime = AgentRuntime::query()
            ->where('id', $runtimeId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $runtime) {
            return response()->json(['error' => 'runtime not found'], 404);
        }

        $claimed = DB::transaction(function () use ($runtime) {
            /** @var AgentTaskQueue|null $queue */
            $queue = AgentTaskQueue::query()
                ->where('status', 'queued')
                ->where('runtime_id', $runtime->id)
                ->whereNotNull('agent_id')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $queue) {
                return null;
            }

            $queue->forceFill([
                'runtime_id' => $runtime->id,
                'status'     => 'dispatched',
                'claimed_at' => now(),
            ])->save();

            $rawTaskToken = 'mat_' . bin2hex(random_bytes(20));
            $taskTokenHash = hash('sha256', $rawTaskToken);

            TaskToken::create([
                'token_hash' => $taskTokenHash,
                'task_id'    => $queue->id,
                'agent_id'   => $queue->agent_id,
                'team_id'    => $runtime->team_id,
                'user_id'    => $runtime->user_id,
                'expires_at' => now()->addHour(),
            ]);

            return ['queue' => $queue, 'auth_token' => $rawTaskToken];
        });

        if (! $claimed) {
            return response()->json(['task' => null]);
        }

        $queue = $claimed['queue'];
        $rawTaskToken = $claimed['auth_token'];

        $queue->loadMissing('task.project');
        $task = $queue->task;
        $project = $task?->project;
        $projectResources = [];
        $repos = [];

        if ($project) {
            $resources = $project->resources()->get();

            foreach ($resources as $resource) {
                $projectResources[] = [
                    'id'            => (string) $resource->id,
                    'resource_type' => $resource->resource_type,
                    'resource_ref'  => $resource->resource_ref ?? [],
                    'label'         => $resource->label,
                ];

                if ($resource->resource_type === 'github_repo') {
                    $repos[] = [
                        'url'         => (string) ($resource->resource_ref['url'] ?? ''),
                        'description' => (string) ($resource->label ?? ''),
                    ];
                }
            }

            if (empty($repos)) {
                $repos = [];
            }
        }

        Log::info('claim response', [
            'runtime_id' => $runtime->id,
            'provider'   => $runtime->provider,
            'team_id'    => $runtime->team_id,
        ]);

        return response()->json([
            'task' => [
                'id'              => $queue->id,
                'task_id'         => (string) $queue->task_id,
                'issue_id'        => 'task-' . $queue->task_id,
                'title'           => (string) $task->title,
                'description'     => $queue->prompt ?: AgentTaskQueue::buildPrompt($task),
                'workspace_id'    => (string) $runtime->team_id,
                'runtime_id'      => $runtime->id,
                'agent_id'        => (string) $queue->agent_id,
                'project_id'      => (string) ($project?->id ?? ''),
                'project_title'   => $project?->name ?? '',
                'provider'        => $runtime->provider,
                'auth_token'      => $rawTaskToken,
                'repos'           => $this->deduplicateRepos($repos),
                'project_resources' => $projectResources,
                'local_directory' => $this->resolveLocalDirectory($projectResources, $runtime->provider),
            ],
        ]);
    }

    public function startTask(Request $request, string $taskId): JsonResponse
    {
        $queue = $this->ownedQueue($request, $taskId);

        if (! $queue) {
            return response()->json(['error' => 'task not found'], 404);
        }

        $queue->forceFill([
            'status'     => 'running',
            'started_at' => now(),
        ])->save();

        $queue->task()->update(['status' => 'in-progress']);

        return response()->json(['status' => 'ok']);
    }

    public function outputTask(Request $request, string $taskId): JsonResponse
    {
        $queue = $this->ownedQueue($request, $taskId);

        if (! $queue) {
            return response()->json(['error' => 'task not found'], 404);
        }

        $data = $request->validate([
            'content' => ['nullable', 'string'],
            'type'    => ['nullable', 'string', 'max:50'],
            'tool'    => ['nullable', 'string', 'max:255'],
            'seq'     => ['nullable', 'integer'],
        ]);

        $content = trim((string) ($data['content'] ?? ''));

        if ($content !== '') {
            $existing = trim((string) ($queue->output ?? ''));
            $queue->output = $existing !== '' ? $existing . "\n" . $content : $content;
        }

        $queue->save();

        return response()->json(['status' => 'ok']);
    }

    public function messages(Request $request, string $taskId): JsonResponse
    {
        $queue = AgentTaskQueue::query()
            ->with(['task.project.teams'])
            ->where('id', $taskId)
            ->first();

        if (! $queue || ! $queue->task || ! $queue->task->project) {
            return response()->json(['error' => 'task not found'], 404);
        }

        $userId = (int) $request->user()->id;
        $hasTeamAccess = $queue->task->project->teams()
            ->whereHas('members', function ($query) use ($userId): void {
                $query->where('users.id', $userId);
            })
            ->exists();

        $hasTaskScopedMat = $this->hasValidMatTokenForTask($request, $queue->id);

        if (! $hasTeamAccess && ! $hasTaskScopedMat) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $data = $request->validate([
            'messages'           => ['required', 'array'],
            'messages.*.seq'     => ['required', 'integer', 'min:0'],
            'messages.*.type'    => ['required', 'string', 'max:50'],
            'messages.*.tool'    => ['nullable', 'string', 'max:255'],
            'messages.*.content' => ['nullable', 'string'],
            'messages.*.input'   => ['nullable', 'array'],
            'messages.*.output'  => ['nullable', 'string'],
        ]);

        $incoming = $data['messages'];
        $incomingSeqs = collect($incoming)
            ->pluck('seq')
            ->filter(static fn ($seq) => is_int($seq))
            ->unique()
            ->values();

        $existingSeqs = AgentTaskMessage::query()
            ->where('task_queue_id', $queue->id)
            ->whereIn('seq', $incomingSeqs->all())
            ->pluck('seq')
            ->map(static fn ($seq) => (int) $seq)
            ->all();

        $seen = array_fill_keys($existingSeqs, true);
        $inserted = 0;

        foreach ($incoming as $message) {
            $seq = (int) $message['seq'];

            if (isset($seen[$seq])) {
                continue;
            }

            AgentTaskMessage::create([
                'task_queue_id' => $queue->id,
                'seq'           => $seq,
                'type'          => (string) $message['type'],
                'tool'          => isset($message['tool']) ? (string) $message['tool'] : null,
                'content'       => isset($message['content']) ? (string) $message['content'] : null,
                'input'         => $message['input'] ?? null,
                'output'        => isset($message['output']) ? (string) $message['output'] : null,
                'created_at'    => now(),
            ]);

            $seen[$seq] = true;
            $inserted++;
        }

        return response()->json([
            'status' => 'ok',
            'count'  => $inserted,
        ]);
    }

    public function completeTask(Request $request, string $taskId): JsonResponse
    {
        $queue = $this->ownedQueue($request, $taskId);

        if (! $queue) {
            return response()->json(['error' => 'task not found'], 404);
        }

        $data = $request->validate([
            'output' => ['nullable', 'string'],
            'pr_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $queue->forceFill([
            'status'       => 'completed',
            'completed_at' => now(),
            'pr_url'       => $data['pr_url'] ?? $queue->pr_url,
        ])->save();

        $queue->task()->update(['status' => 'in-review']);

        TaskToken::query()->where('task_id', $queue->id)->delete();

        return response()->json(['status' => 'ok']);
    }

    public function failTask(Request $request, string $taskId): JsonResponse
    {
        $queue = $this->ownedQueue($request, $taskId);

        if (! $queue) {
            return response()->json(['error' => 'task not found'], 404);
        }

        $data = $request->validate([
            'error' => ['nullable', 'string'],
        ]);

        $queue->forceFill([
            'status'        => 'failed',
            'error_message' => $data['error'] ?? null,
        ])->save();

        $queue->task()->update(['status' => 'open']);

        TaskComment::create([
            'task_id' => $queue->task_id,
            'user_id' => $request->user()->id,
            'body'    => '❌ Agent failed: ' . ($data['error'] ?? 'Unknown error'),
        ]);

        TaskToken::query()->where('task_id', $queue->id)->delete();

        return response()->json(['status' => 'ok']);
    }

    public function cancelTask(Request $request, string $taskId): JsonResponse
    {
        $queue = $this->ownedQueue($request, $taskId);

        if (! $queue) {
            return response()->json(['error' => 'task not found'], 404);
        }

        $queue->update(['status' => 'cancelled']);

        TaskComment::create([
            'task_id' => $queue->task_id,
            'user_id' => $request->user()->id,
            'body'    => '⚠️ Task cancelled.',
        ]);

        TaskToken::query()->where('task_id', $queue->id)->delete();

        return response()->json(['status' => 'ok']);
    }

    private function ownedQueue(Request $request, string $queueId): ?AgentTaskQueue
    {
        $queue = AgentTaskQueue::with('task.project')->find($queueId);

        if (! $queue || ! $queue->task || (int) $queue->task->assigned_to !== (int) $request->user()->id) {
            return null;
        }

        return $queue;
    }

    private function isTeamMember($user, Team $team): bool
    {
        return $team->members()->where('users.id', $user->id)->exists();
    }

    private function teamReposPayload(Team $team): array
    {
        $repos = [];

        $resources = ProjectResource::query()
            ->select(['project_id', 'resource_type', 'resource_ref', 'label', 'position'])
            ->where('resource_type', 'github_repo')
            ->whereHas('project.teams', function ($query) use ($team): void {
                $query->where('teams.id', $team->id);
            })
            ->orderBy('project_id')
            ->orderBy('position')
            ->get();

        foreach ($resources as $resource) {
            $url = trim((string) ($resource->resource_ref['url'] ?? ''));

            if ($url !== '') {
                $repos[] = [
                    'url'         => $url,
                    'description' => (string) ($resource->label ?? ''),
                ];
            }
        }

        return $this->deduplicateRepos($repos);
    }

    private function deduplicateRepos(array $repos): array
    {
        $seen = [];
        $result = [];

        foreach ($repos as $repo) {
            $url = (string) ($repo['url'] ?? '');
            if ($url !== '' && ! isset($seen[$url])) {
                $seen[$url] = true;
                $result[] = $repo;
            }
        }

        return $result;
    }

    private function reposVersion(array $repos): string
    {
        $urls = array_column($repos, 'url');
        $urls = array_filter($urls);

        return hash('sha256', implode(',', $urls));
    }

    private function buildDeviceInfo(string $deviceName, string $version): ?string
    {
        if ($deviceName !== '' && $version !== '') {
            return $deviceName . ' · ' . $version;
        }

        if ($deviceName !== '') {
            return $deviceName;
        }

        return $version !== '' ? $version : null;
    }

    private function resolveLocalDirectory(array $projectResources, string $provider): string
    {
        $authUserId = Auth::id();

        if (! $authUserId) {
            return '';
        }

        $daemonId = AgentRuntime::query()
            ->where('provider', $provider)
            ->where('user_id', $authUserId)
            ->value('daemon_id');

        foreach ($projectResources as $resource) {
            if (
                ($resource['resource_type'] ?? '') === 'local_directory'
                && ($resource['resource_ref']['daemon_id'] ?? '') === $daemonId
            ) {
                return (string) ($resource['resource_ref']['local_path'] ?? '');
            }
        }

        return '';
    }

    private function hasValidMatTokenForTask(Request $request, string $taskId): bool
    {
        $authHeader = (string) $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return false;
        }

        $token = trim((string) $matches[1]);

        if (! str_starts_with($token, 'mat_')) {
            return false;
        }

        $hash = hash('sha256', $token);

        return TaskToken::query()
            ->where('token_hash', $hash)
            ->where('task_id', $taskId)
            ->where('expires_at', '>', now())
            ->exists();
    }
}