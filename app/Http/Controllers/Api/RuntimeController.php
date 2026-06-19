<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentRuntime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuntimeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workspace_id' => ['nullable', 'string'],
        ]);

        $teamIds = $request->user()->teams()->pluck('teams.id')->all();
        $query = AgentRuntime::query()->where(function ($teamQuery) use ($teamIds): void {
            foreach ($teamIds as $index => $teamId) {
                if ($index === 0) {
                    $teamQuery->where('team_id', (int) $teamId);
                } else {
                    $teamQuery->orWhere('team_id', (int) $teamId);
                }
            }
        });

        if (! empty($data['workspace_id']) && is_numeric($data['workspace_id'])) {
            $query->where('team_id', (int) $data['workspace_id']);
        }

        $runtimes = $query->orderBy('name')->get();

        return response()->json($runtimes->map(static function (AgentRuntime $runtime): array {
            return [
                'id'            => (string) $runtime->id,
                'workspace_id'  => (string) $runtime->team_id,
                'daemon_id'     => (string) $runtime->daemon_id,
                'name'          => (string) $runtime->name,
                'runtime_mode'  => 'local',
                'provider'      => (string) $runtime->provider,
                'launch_header' => (string) $runtime->provider,
                'status'        => (string) $runtime->status,
                'device_info'   => $runtime->device_info,
                'metadata'      => (object) ($runtime->metadata ?? []),
                'owner_id'      => (string) ($runtime->user_id ?? ''),
                'visibility'    => 'private',
                'profile_id'    => null,
                'last_seen_at'  => optional($runtime->last_seen_at)?->toIso8601String(),
                'created_at'    => optional($runtime->created_at)?->toIso8601String(),
                'updated_at'    => optional($runtime->updated_at)?->toIso8601String(),
            ];
        })->values());
    }
}
