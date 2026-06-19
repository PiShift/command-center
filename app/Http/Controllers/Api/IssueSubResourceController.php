<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class IssueSubResourceController
{
    public function timeline(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json([]);
        }

        $task->load(['queueEntries.messages' => fn ($query) => $query->orderBy('seq')]);

        $messageEvents = $task->queueEntries
            ->flatMap(function ($queue) {
                return $queue->messages->map(function ($message) use ($queue): array {
                    return [
                        'id'         => (string) $message->id,
                        'type'       => 'agent_message',
                        'actor_type' => 'agent',
                        'actor_id'   => (string) ($queue->agent_id ?? ''),
                        'data'       => [
                            'seq'     => (int) $message->seq,
                            'type'    => (string) $message->type,
                            'tool'    => $message->tool,
                            'content' => $message->content,
                            'input'   => $message->input ?? (object) [],
                            'output'  => $message->output,
                        ],
                        'created_at' => optional($message->created_at)?->toIso8601String(),
                    ];
                });
            })
            ->values();

        $commentEvents = TaskComment::query()
            ->where('task_id', $task->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(static function (TaskComment $comment): array {
                return [
                    'id'         => (string) $comment->id,
                    'type'       => 'comment',
                    'actor_type' => 'user',
                    'actor_id'   => (string) $comment->user_id,
                    'data'       => [
                        'content' => (string) $comment->body,
                    ],
                    'created_at' => optional($comment->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        $events = $messageEvents
            ->merge($commentEvents)
            ->sortBy('created_at')
            ->values();

        return response()->json($events);
    }

    public function subscribers(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task || ! $task->assigned_to) {
            return response()->json([]);
        }

        return response()->json([
            [
                'id'         => (string) $task->assigned_to,
                'issue_id'   => (string) $task->id,
                'user_id'    => (string) $task->assigned_to,
                'user_type'  => 'user',
                'created_at' => optional($task->updated_at ?? $task->created_at)?->toIso8601String(),
            ],
        ]);
    }

    public function usage(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['total_cost' => 0, 'total_tokens' => 0, 'runs' => []]);
        }

        $task->load(['queueEntries.usage']);

        $runs = $task->queueEntries
            ->map(function ($queue): ?array {
                if (! $queue->usage) {
                    return null;
                }

                return [
                    'task_id'       => (string) $queue->id,
                    'cost'          => (float) $queue->usage->cost,
                    'input_tokens'  => (int) $queue->usage->input_tokens,
                    'output_tokens' => (int) $queue->usage->output_tokens,
                    'model'         => $queue->usage->model,
                    'created_at'    => optional($queue->usage->created_at)?->toIso8601String(),
                ];
            })
            ->filter()
            ->values();

        $totalCost = (float) $runs->sum('cost');
        $totalTokens = (int) $runs->sum(function (array $run): int {
            return ((int) $run['input_tokens']) + ((int) $run['output_tokens']);
        });

        return response()->json([
            'total_cost'   => $totalCost,
            'total_tokens' => $totalTokens,
            'runs'         => $runs,
        ]);
    }

    public function attachments(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json([]);
        }

        $media = $task->getMedia('attachments')->merge($task->getMedia('images'));

        return response()->json(
            $media->map(static function (Media $item): array {
                return [
                    'id'           => (string) $item->uuid,
                    'filename'     => (string) $item->file_name,
                    'url'          => $item->getUrl(),
                    'download_url' => $item->getUrl(),
                    'content_type' => (string) ($item->mime_type ?? ''),
                    'size_bytes'   => (int) $item->size,
                    'created_at'   => optional($item->created_at)?->toIso8601String(),
                ];
            })->values()
        );
    }

    public function labels(string $id): JsonResponse
    {
        return response()->json(['labels' => []]);
    }

    public function pullRequests(string $id): JsonResponse
    {
        return response()->json(['pull_requests' => []]);
    }

    public function taskRuns(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json([]);
        }

        $runs = $task->queueEntries()
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($queue) use ($task): array {
                return [
                    'id'             => (string) $queue->id,
                    'agent_id'       => (string) ($queue->agent_id ?? ''),
                    'runtime_id'     => (string) ($queue->runtime_id ?? ''),
                    'issue_id'       => (string) $task->id,
                    'workspace_id'   => (string) ($queue->team_id ?? '1'),
                    'status'         => (string) $queue->status,
                    'priority'       => 0,
                    'started_at'     => optional($queue->started_at)?->toIso8601String(),
                    'completed_at'   => optional($queue->completed_at)?->toIso8601String(),
                    'created_at'     => optional($queue->created_at)?->toIso8601String(),
                    'kind'           => 'direct',
                    'failure_reason' => (string) ($queue->error_message ?? ''),
                    'attempt'        => 1,
                    'max_attempts'   => 1,
                ];
            })
            ->values();

        return response()->json($runs);
    }

    public function children(string $id): JsonResponse
    {
        return response()->json(['issues' => []]);
    }

    public function activeTask(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['tasks' => []]);
        }

        $active = $task->queueEntries()
            ->whereIn('status', ['queued', 'dispatched', 'running'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(static function ($queue): array {
                return [
                    'id'         => (string) $queue->id,
                    'status'     => (string) $queue->status,
                    'agent_id'   => (string) ($queue->agent_id ?? ''),
                    'runtime_id' => (string) ($queue->runtime_id ?? ''),
                    'started_at' => optional($queue->started_at)?->toIso8601String(),
                    'created_at' => optional($queue->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json(['tasks' => $active]);
    }

    public function cancelTask(string $issueId, string $taskId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function rerun(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    private function resolveTask(string $id): ?Task
    {
        if (preg_match('/^[0-9a-f-]{36}$/i', $id) === 1) {
            return Task::query()->whereKey($id)->first();
        }

        if (str_contains($id, '-')) {
            $parts = explode('-', $id);
            $number = end($parts);

            if (is_numeric($number)) {
                return Task::query()->whereKey((int) $number)->first();
            }
        }

        if (is_numeric($id)) {
            return Task::query()->whereKey((int) $id)->first();
        }

        return null;
    }
}
