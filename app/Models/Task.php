<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Task extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'project_id',
        'sprint_id',
        'assigned_to',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'due_date',
        'estimated_hours',
        'weight',
        'labels',
        'completed_at',
        'source',
        'original_input',
        'guide',
        'overdue_notified_at',
    ];

    protected $casts = [
        'due_date'             => 'date',
        'completed_at'         => 'datetime',
        'overdue_notified_at'  => 'datetime',
        'labels'               => 'array',
        'weight'               => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->with('author')->latest();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('sort_order');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('local');

        $this->addMediaCollection('images')
            ->useDisk('local')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->nonQueued()
            ->performOnCollections('images');
    }
}
