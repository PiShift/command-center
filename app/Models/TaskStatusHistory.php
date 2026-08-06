<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStatusHistory extends Model
{
    protected $fillable = [
        'task_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_user_id',
        'actor_agent_id',
        'actor_label',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'actor_agent_id');
    }
}
