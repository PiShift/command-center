<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTaskUsage extends Model
{
    public $timestamps = false;

    protected $table = 'agent_task_usage';

    protected $fillable = [
        'task_queue_id',
        'input_tokens',
        'output_tokens',
        'cost',
        'model',
        'created_at',
    ];

    protected $casts = [
        'input_tokens'  => 'integer',
        'output_tokens' => 'integer',
        'cost'          => 'decimal:6',
        'created_at'    => 'datetime',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(AgentTaskQueue::class, 'task_queue_id');
    }
}