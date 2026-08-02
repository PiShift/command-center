<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AgentTaskQueue extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'agent_task_queue';

    protected $fillable = [
        'id',
        'task_id',
        'team_id',
        'runtime_id',
        'agent_id',
        'status',
        'prompt',
        'output',
        'error_message',
        'pr_url',
        'claimed_at',
        'started_at',
        'completed_at',
        'trigger_comment_id',
        'trigger_comment_content',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $queue): void {
            if (empty($queue->id)) {
                $queue->id = (string) Str::uuid();
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function runtime(): BelongsTo
    {
        return $this->belongsTo(AgentRuntime::class, 'runtime_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentTaskMessage::class, 'task_queue_id');
    }

    public function usage(): HasOne
    {
        return $this->hasOne(AgentTaskUsage::class, 'task_queue_id');
    }

    public static function buildPrompt(Task $task): string
    {
        $task->loadMissing('checklists', 'project');

        $lines = [];
        $lines[] = '# '.trim((string) $task->title);
        $lines[] = '';
        $lines[] = '## Description';
        $lines[] = trim((string) ($task->description ?? '')) !== '' ? trim((string) $task->description) : '_No description provided._';

        if (trim((string) ($task->guide ?? '')) !== '') {
            $lines[] = '';
            $lines[] = '## Implementation Guide';
            $lines[] = trim((string) $task->guide);
        }

        $checklistItems = $task->checklists?->pluck('label')->map(fn ($label) => trim((string) $label))->filter()->values() ?? collect();

        if ($checklistItems->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Checklist';

            foreach ($checklistItems as $item) {
                $lines[] = '- '.$item;
            }
        }

        if (trim((string) ($task->project?->guide ?? '')) !== '') {
            $lines[] = '';
            $lines[] = '## Project Guide';
            $lines[] = trim((string) $task->project->guide);
        }

        return implode("\n", $lines);
    }
}
