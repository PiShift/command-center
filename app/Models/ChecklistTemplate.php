<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    public const TYPES = ['bug', 'feature', 'change'];

    protected $fillable = [
        'project_id',
        'name',
        'type',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class)->orderBy('sort_order');
    }

    /**
     * Templates whose rules match the given project + task type.
     * A null project_id means "all projects"; a null type means "all types".
     */
    public function scopeMatching(Builder $query, ?int $projectId, ?string $type): Builder
    {
        return $query
            ->where(function (Builder $q) use ($projectId) {
                $q->whereNull('project_id');

                if ($projectId) {
                    $q->orWhere('project_id', $projectId);
                }
            })
            ->where(function (Builder $q) use ($type) {
                $q->whereNull('type');

                if ($type) {
                    $q->orWhere('type', $type);
                }
            });
    }
}
