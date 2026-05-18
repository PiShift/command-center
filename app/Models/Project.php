<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'description',
        'guide',
        'github_repo',
        'stack',
        'color',
        'status',
        'start_date',
        'deadline',
        'budget',
        'health',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline'   => 'date',
        'budget'     => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'project_team');
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class)->orderBy('sort_order');
    }

    public function backlogItems(): HasMany
    {
        return $this->hasMany(BacklogItem::class)->orderBy('sort_order');
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'complete';
    }

    public function openTasksCount(): int
    {
        return $this->tasks()->where('status', '!=', 'done')->count();
    }
}
