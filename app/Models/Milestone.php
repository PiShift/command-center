<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'deadline',
        'sort_order',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Computes progress as the ratio of done-task weight to total task weight.
     * Falls back to 0 if no tasks or no tasks have a weight set.
     */
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
