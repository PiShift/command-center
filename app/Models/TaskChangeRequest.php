<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TaskChangeRequest extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'task_id',
        'task_status_history_id',
        'category',
        'explanation',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function statusHistory(): BelongsTo
    {
        return $this->belongsTo(TaskStatusHistory::class, 'task_status_history_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('local');
    }
}
