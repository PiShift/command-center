<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agent extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'team_id',
        'runtime_id',
        'owner_id',
        'name',
        'description',
        'instructions',
        'visibility',
        'status',
        'max_concurrent_tasks',
        'model',
        'custom_env',
        'custom_args',
        'archived_at',
    ];

    protected $casts = [
        'custom_env' => 'array',
        'custom_args' => 'array',
        'archived_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $agent): void {
            if (empty($agent->id)) {
                $agent->id = (string) Str::uuid();
            }
        });
    }

    public function scopeWorkspace($query)
    {
        return $query->where('visibility', 'workspace');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function runtime(): BelongsTo
    {
        return $this->belongsTo(AgentRuntime::class, 'runtime_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTaskQueue::class, 'agent_id');
    }
}
