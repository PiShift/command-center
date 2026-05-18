<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacklogItem extends Model
{
    protected $fillable = [
        'project_id',
        'sprint_id',
        'title',
        'description',
        'guide',
        'status',
        'sort_order',
        'promoted',
        'promoted_task_id',
        'promoted_at',
    ];

    protected $casts = [
        'promoted'    => 'boolean',
        'promoted_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    public function promotedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'promoted_task_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('promoted', false);
    }

    public function scopePromoted(Builder $query): Builder
    {
        return $query->where('promoted', true);
    }
}
