<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        Log::info('create agent request', $request->all());

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'instructions'         => ['nullable', 'string'],
            'runtime_id'           => ['required', 'uuid'],
            'visibility'           => ['nullable', 'in:workspace,private'],
            'max_concurrent_tasks' => ['nullable', 'integer', 'min:1', 'max:50'],
            'model'                => ['nullable', 'string', 'max:255'],
            'custom_env'           => ['nullable', 'array'],
            'custom_args'          => ['nullable', 'array'],
        ]);

        // Resolve runtime — must belong to authenticated user
        $runtime = AgentRuntime::query()
            ->where('id', $data['runtime_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$runtime) {
            return response()->json(['error' => 'runtime not found'], 404);
        }

        // Resolve team from runtime
        $team = Team::find($runtime->team_id);
        if (!$team) {
            return response()->json(['error' => 'workspace not found'], 404);
        }

        $agent = Agent::create([
            'team_id'               => $team->id,
            'runtime_id'            => $runtime->id,
            'owner_id'              => $request->user()->id,
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'instructions'          => $data['instructions'] ?? null,
            'visibility'            => $data['visibility'] ?? 'workspace',
            'status'                => 'idle',
            'max_concurrent_tasks'  => (int) ($data['max_concurrent_tasks'] ?? 6),
            'model'                 => $data['model'] ?? null,
            'custom_env'            => $data['custom_env'] ?? null,
            'custom_args'           => $data['custom_args'] ?? null,
        ]);

        return response()->json($this->agentPayload($agent->load('runtime')), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $agents = Agent::query()
            ->where('owner_id', $request->user()->id)
            ->whereNull('archived_at')
            ->with('runtime')
            ->orderBy('name')
            ->get();

        return response()->json(
            $agents->map(fn (Agent $agent): array => $this->agentPayload($agent))->values()
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $agent = Agent::with('runtime')->find($id);

        if (! $agent || $agent->archived_at) {
            return response()->json(['error' => 'not found'], 404);
        }

        if (! $this->canView($request, $agent)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        return response()->json($this->agentPayload($agent));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $agent = Agent::find($id);

        if (! $agent || $agent->archived_at) {
            return response()->json(['error' => 'not found'], 404);
        }

        if ((int) $agent->owner_id !== (int) $request->user()->id) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $data = $request->validate([
            'name'                 => ['sometimes', 'required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'instructions'         => ['nullable', 'string'],
            'visibility'           => ['sometimes', 'in:workspace,private'],
            'status'               => ['sometimes', 'in:idle,working,blocked,error,offline'],
            'max_concurrent_tasks' => ['nullable', 'integer', 'min:1', 'max:50'],
            'model'                => ['nullable', 'string', 'max:255'],
            'runtime_id'           => ['nullable', 'uuid'],
            'custom_env'           => ['nullable', 'array'],
            'custom_args'          => ['nullable', 'array'],
        ]);

        if (! empty($data['runtime_id'])) {
            $runtime = AgentRuntime::query()
                ->where('id', $data['runtime_id'])
                ->where('user_id', $request->user()->id)
                ->where('team_id', $agent->team_id)
                ->first();

            if (! $runtime) {
                return response()->json(['error' => 'runtime not found'], 404);
            }
        }

        $agent->update($data);

        return response()->json($this->agentPayload($agent->load('runtime')));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $agent = Agent::find($id);

        if (! $agent || $agent->archived_at) {
            return response()->json(['error' => 'not found'], 404);
        }

        if ((int) $agent->owner_id !== (int) $request->user()->id) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $agent->update([
            'archived_at' => now(),
            'status'      => 'offline',
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function canView(Request $request, Agent $agent): bool
    {
        if ((int) $agent->owner_id === (int) $request->user()->id) {
            return true;
        }

        if ($agent->visibility !== 'workspace') {
            return false;
        }

        return $request->user()->teams()->where('teams.id', $agent->team_id)->exists();
    }

    private function agentPayload(Agent $agent): array
    {
        return [
            'id'                   => $agent->id,
            'workspace_id'         => (string) $agent->team_id,
            'runtime_id'           => $agent->runtime_id,
            'name'                 => $agent->name,
            'description'          => $agent->description ?? '',
            'instructions'         => $agent->instructions ?? '',
            'avatar_url'           => null,
            'runtime_mode'         => 'local',
            'runtime_config'       => (object) [],
            'custom_args'          => $agent->custom_args ?? [],
            'custom_env'           => null,
            'has_custom_env'       => false,
            'custom_env_key_count' => 0,
            'visibility'           => $agent->visibility,
            'status'               => $agent->status,
            'max_concurrent_tasks' => $agent->max_concurrent_tasks,
            'model'                => $agent->model ?? '',
            'thinking_level'       => '',
            'owner_id'             => (string) $agent->owner_id,
            'skills'               => [],
            'created_at'           => $agent->created_at->toIso8601String(),
            'updated_at'           => $agent->updated_at->toIso8601String(),
            'archived_at'          => $agent->archived_at?->toIso8601String(),
            'archived_by'          => null,
            'runtime'              => $agent->runtime ? [
                'id'           => $agent->runtime->id,
                'workspace_id' => (string) $agent->runtime->team_id,
                'daemon_id'    => $agent->runtime->daemon_id,
                'name'         => $agent->runtime->name,
                'runtime_mode' => 'local',
                'provider'     => $agent->runtime->provider,
                'launch_header'=> $agent->runtime->provider,
                'status'       => $agent->runtime->status,
                'device_info'  => $agent->runtime->device_info,
                'metadata'     => $agent->runtime->metadata ?? (object) [],
                'owner_id'     => (string) $agent->runtime->user_id,
                'visibility'   => 'private',
                'profile_id'   => null,
                'last_seen_at' => $agent->runtime->last_seen_at?->toIso8601String(),
                'created_at'   => $agent->runtime->created_at->toIso8601String(),
                'updated_at'   => $agent->runtime->updated_at->toIso8601String(),
            ] : null,
        ];
    }
}
