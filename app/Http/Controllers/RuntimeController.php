<?php

namespace App\Http\Controllers;

use App\Models\AgentRuntime;
use Illuminate\View\View;

class RuntimeController extends Controller
{
    /**
     * Display a listing of all agent runtimes.
     */
    public function index(): View
    {
        $runtimes = AgentRuntime::with('user')
            ->withCount('agents')
            ->get();

        $machines = $runtimes
            ->groupBy(fn (AgentRuntime $runtime) => $runtime->daemon_id ?: $runtime->id)
            ->map(function ($group, string $daemonId) {
                $ordered = $group
                    ->sortBy(fn (AgentRuntime $runtime) => [
                        $runtime->status === 'online' ? 0 : 1,
                        strtolower($runtime->provider ?? ''),
                        strtolower($runtime->name ?? ''),
                    ])
                    ->values();

                $first = $ordered->first();
                $hostname = trim(str((string) ($first?->device_info ?? $first?->name ?? 'Unknown machine'))->before('·')->toString());
                $onlineCount = $ordered->where('status', 'online')->count();
                $latestSeen = $ordered
                    ->filter(fn (AgentRuntime $runtime) => (bool) $runtime->last_seen_at)
                    ->sortByDesc('last_seen_at')
                    ->first()?->last_seen_at;

                return [
                    'daemon_id' => $daemonId,
                    'hostname' => $hostname !== '' ? $hostname : 'Unknown machine',
                    'cli_version' => $first?->cli_version,
                    'is_online' => $onlineCount > 0,
                    'online_count' => $onlineCount,
                    'runtime_count' => $ordered->count(),
                    'work_label' => $ordered->sum('agents_count') > 0 ? 'Working' : 'All idle',
                    'providers' => $ordered->pluck('provider')->filter()->unique()->values(),
                    'last_seen_at' => $latestSeen,
                    'runtimes' => $ordered,
                ];
            })
            ->sortByDesc(fn (array $machine) => [
                $machine['is_online'] ? 1 : 0,
                optional($machine['last_seen_at'])->getTimestamp() ?? 0,
            ])
            ->values();

        return view('runtimes.index', [
            'machines' => $machines,
        ]);
    }
}
