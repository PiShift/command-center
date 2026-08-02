<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TaskComment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['task_id', 'user_id', 'agent_id', 'body'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachment')
            ->useDisk('local')
            ->singleFile();
    }
}
