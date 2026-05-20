<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\TaskCommentNotification;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.view'), 403);

        $data = $request->validate([
            'body'       => 'required|string|max:2000',
            'attachment' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar',
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'body'    => $data['body'],
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $comment->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->toMediaCollection('attachment');
        }

        if ($request->expectsJson()) {
            $comment->load('author');
            $attachment = $comment->getFirstMedia('attachment');
            return response()->json([
                'id'         => $comment->id,
                'body'       => $comment->body,
                'author'     => $comment->author->name,
                'color'      => $comment->author->color,
                'initials'   => $comment->author->initials ?? strtoupper(substr($comment->author->name, 0, 2)),
                'created_at' => $comment->created_at->diffForHumans(),
                'attachment' => $attachment ? [
                    'id'        => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'mime_type' => $attachment->mime_type,
                    'size'      => $attachment->size,
                    'url'       => route('comment-attachments.destroy', ['task' => $task->id, 'comment' => $comment->id]),
                ] : null,
            ]);
        }

        // Notify assignee and previous commenters (excluding commenter)
        $commenter = auth()->user();
        $recipients = collect();

        if ($task->assigned_to && $task->assigned_to !== $commenter->id) {
            $recipients->push($task->assignee);
        }

        $prevCommenters = $task->comments()
            ->where('user_id', '!=', $commenter->id)
            ->where('id', '!=', $comment->id)
            ->pluck('user_id')
            ->unique()
            ->map(fn($id) => \App\Models\User::find($id))
            ->filter();

        $recipients = $recipients->merge($prevCommenters)->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskCommentNotification($task->load('project'), $comment, $commenter));
        }

        SlackNotificationHelper::notifyOnce(new TaskCommentNotification($task->load('project'), $comment, $commenter));

        return back()->with('success', 'Comment added.');
    }

    public function destroy(Task $task, TaskComment $comment)
    {
        abort_unless($comment->task_id === $task->id, 404);
        $user = auth()->user();
        abort_unless(
            $comment->user_id === $user->id || $user->hasPermission('tasks.comments.delete'),
            403
        );

        $comment->delete();

        return back();
    }
}
