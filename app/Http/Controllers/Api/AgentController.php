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

        return response()->json($agent->load('runtime'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $agents = Agent::query()
            ->where('owner_id', $request->user()->id)
            ->whereNull('archived_at')
            ->with(['runtime:id,name,provider,status'])
            ->orderBy('name')
            ->get();

        return response()->json($agents);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $agent = Agent::with(['runtime:id,name,provider,status'])->find($id);

        if (! $agent || $agent->archived_at) {
            return response()->json(['error' => 'not found'], 404);
        }

        if (! $this->canView($request, $agent)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        return response()->json($agent);
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

        return response()->json($agent->load('runtime:id,name,provider,status'));
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
}
