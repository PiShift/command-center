<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class TaskCommentAttachmentController extends Controller
{
    public function store(Request $request, Task $task, TaskComment $comment)
    {
        abort_unless($comment->task_id === $task->id, 404);
        $user = auth()->user();
        abort_unless(
            $comment->user_id === $user->id || $user->hasPermission('tasks.edit_any'),
            403
        );

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar',
            ],
        ]);

        $file  = $request->file('file');
        $media = $comment->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->toMediaCollection('attachment');

        return response()->json([
            'id'         => $media->id,
            'name'       => $media->name,
            'file_name'  => $media->file_name,
            'mime_type'  => $media->mime_type,
            'size'       => $media->size,
            'url'        => route('comment-attachments.destroy', ['task' => $task->id, 'comment' => $comment->id]),
            'created_at' => $media->created_at->diffForHumans(),
        ]);
    }

    public function destroy(Task $task, TaskComment $comment)
    {
        abort_unless($comment->task_id === $task->id, 404);
        $user = auth()->user();
        abort_unless(
            $comment->user_id === $user->id || $user->hasPermission('tasks.edit_any'),
            403
        );

        $media = $comment->getFirstMedia('attachment');
        if ($media) {
            $media->delete();
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function download(Request $request, Task $task, TaskComment $comment)
    {
        abort_unless(auth()->user()->hasPermission('tasks.view'), 403);
        abort_unless($comment->task_id === $task->id, 404);

        $media = $comment->getFirstMedia('attachment');
        abort_unless($media, 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
