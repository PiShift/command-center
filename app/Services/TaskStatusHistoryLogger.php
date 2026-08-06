<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskStatusHistory;
use Illuminate\Support\Facades\Auth;

class TaskStatusHistoryLogger
{
    public static function log(Task $task, string $fromStatus, string $toStatus, array $actor = []): void
    {
        if ($fromStatus === $toStatus) {
            return;
        }

        $resolved = self::resolveActor($actor);

        TaskStatusHistory::query()->create([
            'task_id' => $task->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_type' => $resolved['type'],
            'actor_user_id' => $resolved['user_id'],
            'actor_agent_id' => $resolved['agent_id'],
            'actor_label' => $resolved['label'],
        ]);
    }

    private static function resolveActor(array $actor): array
    {
        if (! empty($actor)) {
            return [
                'type' => (string) ($actor['type'] ?? 'system'),
                'user_id' => $actor['user_id'] ?? null,
                'agent_id' => $actor['agent_id'] ?? null,
                'label' => $actor['label'] ?? null,
            ];
        }

        $authUser = Auth::user();

        if ($authUser) {
            return [
                'type' => 'user',
                'user_id' => $authUser->id,
                'agent_id' => null,
                'label' => null,
            ];
        }

        if (app()->bound('request')) {
            $authorization = (string) request()->header('Authorization', '');

            if (str_starts_with($authorization, 'Bearer mat_')) {
                return [
                    'type' => 'daemon',
                    'user_id' => null,
                    'agent_id' => null,
                    'label' => 'Daemon API',
                ];
            }
        }

        return [
            'type' => 'system',
            'user_id' => null,
            'agent_id' => null,
            'label' => 'System',
        ];
    }
}
