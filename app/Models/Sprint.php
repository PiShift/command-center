<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    protected $table = 'sprints';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'deadline',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date',
        'status'   => 'string',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'sprint_id');
    }

    public function backlogItems(): HasMany
    {
        return $this->hasMany(BacklogItem::class, 'sprint_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    // ── Helper methods ────────────────────────────────────────────────────────

    public function isPublishable(): bool
    {
        return $this->status === 'draft' && $this->tasks()->count() > 0;
    }

    public function publish(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function complete(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    public function unpublish(): void
    {
        $this->status = 'draft';
        $this->save();
    }

    // ── Computed attributes ───────────────────────────────────────────────────

    public function getProgressPercentAttribute(): int
    {
        $tasks = $this->tasks;
        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalWeight = $tasks->sum(fn ($t) => $t->weight ?? 1);
        if ($totalWeight === 0) {
            return 0;
        }

        $doneWeight = $tasks
            ->where('status', 'done')
            ->sum(fn ($t) => $t->weight ?? 1);

        return (int) round(($doneWeight / $totalWeight) * 100);
    }

    public function getDonePointsAttribute(): int
    {
        return (int) $this->tasks->where('status', 'done')->sum(fn ($t) => $t->weight ?? 1);
    }

    public function getTotalPointsAttribute(): int
    {
        return (int) $this->tasks->sum(fn ($t) => $t->weight ?? 1);
    }
}
