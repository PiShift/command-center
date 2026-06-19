<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTaskQueue;
use App\Models\KanbanColumn;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function show(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        return response()->json($this->issuePayload($task));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        $data = $request->validate([
            'status'        => ['sometimes', 'string', 'max:100'],
            'priority'      => ['sometimes', 'string'],
            'assignee_id'   => ['sometimes', 'nullable', 'string'],
            'assignee_type' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('status', $data)) {
            $mappedStatus = $this->mapIncomingStatus((string) $data['status']);

            if ($mappedStatus !== null) {
                $task->status = $mappedStatus;
            }
        }

        if (array_key_exists('priority', $data)) {
            $priority = strtolower(trim((string) $data['priority']));

            if (in_array($priority, ['low', 'medium', 'high'], true)) {
                $task->priority = $priority;
            }
        }

        if (array_key_exists('assignee_id', $data) && array_key_exists('assignee_type', $data)) {
            $assigneeType = strtolower(trim((string) ($data['assignee_type'] ?? '')));

            if ($assigneeType === 'user') {
                $assigneeRaw = trim((string) ($data['assignee_id'] ?? ''));

                if ($assigneeRaw !== '' && is_numeric($assigneeRaw)) {
                    $task->assigned_to = (int) $assigneeRaw;
                }
            }
        }

        if ($task->isDirty()) {
            $task->save();
        }

        return response()->json($this->issuePayload($task->fresh()));
    }

    public function comments(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        $comments = TaskComment::query()
            ->where('task_id', $task->id)
            ->with('author:id,name')
            ->orderBy('created_at')
            ->get();

        return response()->json(
            $comments->map(fn (TaskComment $comment): array => $this->commentPayload($comment))->values()
        );
    }

    public function storeComment(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body'    => $data['content'],
        ]);

        return response()->json($this->commentPayload($comment), 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        if (! $task) {
            return response()->json(['error' => 'issue not found'], 404);
        }

        Task::query()->whereKey($task->id)->delete();

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

    private function commentPayload(TaskComment $comment): array
    {
        return [
            'id'               => (string) $comment->id,
            'issue_id'         => (string) $comment->task_id,
            'author_type'      => 'user',
            'author_id'        => (string) $comment->user_id,
            'content'          => (string) $comment->body,
            'type'             => 'comment',
            'parent_id'        => null,
            'created_at'       => optional($comment->created_at)?->toIso8601String(),
            'updated_at'       => optional($comment->updated_at)?->toIso8601String(),
            'resolved_at'      => null,
            'resolved_by_type' => null,
            'resolved_by_id'   => null,
            'reactions'        => [],
            'attachments'      => [],
        ];
    }

    private function issuePayload(Task $task): array
    {
        $task->loadMissing(['project.teams:id', 'assignee', 'checklists']);

        $workspaceId = $task->project?->teams?->first()?->id;
        $assigneeId = $task->assigned_to ? (string) $task->assigned_to : null;

        return [
            'id'              => (string) $task->id,
            'workspace_id'    => $workspaceId ? (string) $workspaceId : '',
            'number'          => (int) $task->id,
            'identifier'      => 'task-' . $task->id,
            'title'           => (string) $task->title,
            'description'     => AgentTaskQueue::buildPrompt($task),
            'status'          => (string) $task->status,
            'priority'        => (string) $task->priority,
            'assignee_type'   => $assigneeId ? 'user' : null,
            'assignee_id'     => $assigneeId,
            'creator_type'    => 'user',
            'creator_id'      => $assigneeId,
            'parent_issue_id' => null,
            'project_id'      => (string) $task->project_id,
            'position'        => 0.0,
            'start_date'      => null,
            'due_date'        => optional($task->due_date)?->toDateString(),
            'created_at'      => optional($task->created_at)?->toIso8601String(),
            'updated_at'      => optional($task->updated_at)?->toIso8601String(),
            'metadata'        => (object) [],
            'reactions'       => [],
            'attachments'     => [],
            'labels'          => [],
        ];
    }

    private function mapIncomingStatus(string $status): ?string
    {
        $incoming = strtolower(trim($status));

        $statusMap = [
            'todo'        => 'open',
            'open'        => 'open',
            'in_progress' => 'in-progress',
            'in-progress' => 'in-progress',
            'in_review'   => 'in-review',
            'in-review'   => 'in-review',
            'done'        => 'done',
            'completed'   => 'done',
            'backlog'     => 'open',
            'blocked'     => 'in-progress',
            'cancelled'   => 'done',
        ];

        if (array_key_exists($incoming, $statusMap)) {
            return $statusMap[$incoming];
        }

        return KanbanColumn::query()->where('slug', $incoming)->exists() ? $incoming : null;
    }
}
