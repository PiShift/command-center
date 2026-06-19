<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTaskQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentTaskSnapshotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $teamIds = $user->teams()->pluck('teams.id')->all();

        $query = AgentTaskQueue::query()
            ->where(function ($statusQuery): void {
                $statusQuery
                    ->where('status', 'queued')
                    ->orWhere('status', 'dispatched')
                    ->orWhere('status', 'running');
            })
            ->with('task:id');

        if (! $user->hasPermission('projects.view_all')) {
            $query->whereIn('team_id', $teamIds);
        }

        $queues = $query->orderByDesc('created_at')->get();

        return response()->json($queues->map(static function (AgentTaskQueue $queue): array {
            return [
                'id'            => (string) $queue->id,
                'agent_id'      => (string) ($queue->agent_id ?? ''),
                'runtime_id'    => (string) ($queue->runtime_id ?? ''),
                'issue_id'      => $queue->task_id ? 'task-' . $queue->task_id : '',
                'workspace_id'  => (string) $queue->team_id,
                'status'        => (string) $queue->status,
                'priority'      => 0,
                'dispatched_at' => optional($queue->claimed_at)?->toIso8601String(),
                'started_at'    => optional($queue->started_at)?->toIso8601String(),
                'completed_at'  => optional($queue->completed_at)?->toIso8601String(),
                'result'        => null,
                'error'         => $queue->error_message,
                'failure_reason'=> '',
                'attempt'       => 1,
                'max_attempts'  => 1,
                'parent_task_id'=> null,
                'created_at'    => optional($queue->created_at)?->toIso8601String(),
                'kind'          => 'direct',
            ];
        })->values());
    }
}
