<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskAttachmentController extends Controller
{
    private array $acceptedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/zip',
        'application/x-rar-compressed',
        'application/vnd.rar',
    ];

    public function store(Request $request, Task $task)
    {
        $user = auth()->user();
        abort_unless(
            $user->hasPermission('tasks.edit_own') || $user->hasPermission('tasks.edit_any'),
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

        $file     = $request->file('file');
        $mimeType = $file->getMimeType();
        $isImage  = str_starts_with($mimeType, 'image/');
        $collection = $isImage ? 'images' : 'attachments';

        $media = $task->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->toMediaCollection($collection);

        return response()->json([
            'id'         => $media->id,
            'name'       => $media->name,
            'file_name'  => $media->file_name,
            'mime_type'  => $media->mime_type,
            'size'       => $media->size,
            'collection' => $media->collection_name,
            'url'        => route('attachments.download', ['task' => $task->id, 'media' => $media->id]),
            'thumb_url'  => $isImage ? route('attachments.download', ['task' => $task->id, 'media' => $media->id, 'thumb' => 1]) : null,
            'uploader'   => $user->name,
            'created_at' => $media->created_at->diffForHumans(),
        ]);
    }

    public function destroy(Task $task, Media $media)
    {
        $user = auth()->user();
        abort_unless($media->model_type === Task::class && (int) $media->model_id === $task->id, 404);
        abort_unless(
            ($media->custom_properties['uploaded_by'] ?? null) === $user->id
                || $user->hasPermission('tasks.edit_any'),
            403
        );

        $media->delete();

        return response()->json(['success' => true]);
    }

    public function download(Request $request, Task $task, Media $media)
    {
        abort_unless(auth()->user()->hasPermission('tasks.view'), 403);
        abort_unless($media->model_type === Task::class && (int) $media->model_id === $task->id, 404);

        if ($request->boolean('thumb') && $media->collection_name === 'images' && $media->hasGeneratedConversion('thumb')) {
            return response()->file($media->getPath('thumb'));
        }

        return response()->download($media->getPath(), $media->file_name);
    }
}
