<?php

namespace App\Http\Controllers\Api;

use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController
{
    public function update(Request $request, string $commentId): JsonResponse
    {
        $comment = TaskComment::query()->whereKey((int) $commentId)->first();

        if (! $comment) {
            return response()->json(['error' => 'not found'], 404);
        }

        $data = $request->validate(['content' => ['required', 'string']]);
        $comment->update(['body' => $data['content']]);

        return response()->json($this->payload($comment->fresh()));
    }

    public function destroy(string $commentId): JsonResponse
    {
        $comment = TaskComment::query()->whereKey((int) $commentId)->first();

        if ($comment) {
            TaskComment::query()->whereKey((int) $commentId)->delete();
        }

        return response()->json(['status' => 'ok']);
    }

    public function resolve(string $commentId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function unresolve(string $commentId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function addReaction(string $commentId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function removeReaction(string $commentId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function triggerPreview(string $issueId): JsonResponse
    {
        return response()->json(['agents' => [], 'squads' => []]);
    }

    public function addIssueReaction(string $issueId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function removeIssueReaction(string $issueId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    private function payload(TaskComment $comment): array
    {
        return [
            'id' => (string) $comment->id,
            'issue_id' => (string) $comment->task_id,
            'author_type' => 'user',
            'author_id' => (string) $comment->user_id,
            'content' => (string) $comment->body,
            'type' => 'comment',
            'parent_id' => null,
            'created_at' => optional($comment->created_at)?->toIso8601String(),
            'updated_at' => optional($comment->updated_at)?->toIso8601String(),
            'resolved_at' => null,
            'resolved_by_type' => null,
            'resolved_by_id' => null,
            'reactions' => [],
            'attachments' => [],
        ];
    }
}
