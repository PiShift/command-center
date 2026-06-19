<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTaskMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_queue_id',
        'seq',
        'type',
        'tool',
        'content',
        'input',
        'output',
        'created_at',
    ];

    protected $casts = [
        'seq'        => 'integer',
        'input'      => 'array',
        'created_at' => 'datetime',
    ];

    public function taskQueue(): BelongsTo
    {
        return $this->belongsTo(AgentTaskQueue::class, 'task_queue_id');
    }
}
