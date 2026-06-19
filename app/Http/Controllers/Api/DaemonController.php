<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentRuntime;
use App\Models\AgentTaskQueue;
use App\Models\Team;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $team = Team::find($teamId);

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
        $team = Team::find($teamId);

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

        if (! $request->user()->is_agent) {
            return response()->json(['task' => null]);
        }

        $queue = DB::transaction(function () use ($request, $runtime) {
            $teamId = $runtime->team_id;

            return AgentTaskQueue::query()
                ->where('status', 'queued')
                ->whereHas('task', fn ($query) => $query->where('assigned_to', $request->user()->id))
                ->whereHas('task.project.teams', fn ($query) => $query->where('teams.id', $teamId))
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();
        });

        if (! $queue) {
            return response()->json(['task' => null]);
        }

        $queue->forceFill([
            'runtime_id' => $runtime->id,
            'status'     => 'dispatched',
            'claimed_at' => now(),
        ])->save();

        $queue->loadMissing('task.project');
        $task = $queue->task;
        $project = $task?->project;
        $repos = [];

        if ($project) {
            $projectRepos = is_array($project->repos) ? $project->repos : [];
            foreach ($projectRepos as $repo) {
                if (is_array($repo)) {
                    $repos[] = $repo;
                }
            }
        }

        return response()->json([
            'task' => [
                'id'              => $queue->id,
                'task_id'         => $queue->task_id,
                'issue_id'        => 'task-' . $queue->task_id,
                'title'           => $task?->title ?? '',
                'description'     => $queue->prompt ?: ($task ? AgentTaskQueue::buildPrompt($task) : ''),
                'workspace_id'    => $project?->id,
                'local_directory' => $this->resolveLocalDirectory($repos, $runtime->provider),
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

        if (($data['type'] ?? null) === 'text' && mb_strlen($content) >= 10) {
            TaskComment::create([
                'task_id' => $queue->task_id,
                'user_id' => $request->user()->id,
                'body'    => $content,
            ]);
        }

        return response()->json(['status' => 'ok']);
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

        $message = '✅ Agent completed this task.';
        $output = trim((string) ($data['output'] ?? ''));
        if ($output !== '') {
            $message .= "\n\n" . substr($output, 0, 1000);
        }

        TaskComment::create([
            'task_id' => $queue->task_id,
            'user_id' => $request->user()->id,
            'body'    => $message,
        ]);

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

        $projects = $team->projects()->select('projects.repos')->get();

        foreach ($projects as $project) {
            $projectRepos = is_array($project->repos) ? $project->repos : [];
            foreach ($projectRepos as $repo) {
                if (is_array($repo) && ! empty($repo['url'])) {
                    $repos[] = $repo;
                }
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

    private function resolveLocalDirectory(array $repos, string $provider): string
    {
        $provider = strtolower(trim($provider));

        foreach ($repos as $repo) {
            $stack = strtolower(trim((string) ($repo['stack'] ?? '')));

            if ($stack !== '' && ($stack === $provider || str_contains($stack, $provider) || str_contains($provider, $stack))) {
                return (string) ($repo['local_path'] ?? '');
            }
        }

        return (string) ($repos[0]['local_path'] ?? '');
    }
}