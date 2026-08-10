<?php

namespace App\Models;

use App\Services\TaskStatusHistoryLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Task extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected ?array $pendingStatusTransition = null;

    /**
     * Set by TaskStatusService when it persists a status change, so the
     * fallback history hook in booted() skips that save.
     */
    public bool $history_written = false;

    protected $fillable = [
        'project_id',
        'sprint_id',
        'assigned_to',
        'agent_id',
        'title',
        'description',
        'type',
        'component',
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
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'labels' => 'array',
        'weight' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $task): void {
            if (! $task->isDirty('status')) {
                return;
            }

            $task->pendingStatusTransition = [
                'from' => (string) $task->getOriginal('status'),
                'to' => (string) $task->status,
            ];
        });

        static::updated(function (self $task): void {
            if (! $task->pendingStatusTransition) {
                return;
            }

            // Status history is a post-function of TaskStatusService; when the
            // service persisted this save it already wrote the history row.
            // This hook remains as a fallback so any path that bypasses the
            // service still leaves an audit trail.
            if ($task->history_written) {
                $task->history_written = false;
                $task->pendingStatusTransition = null;

                return;
            }

            TaskStatusHistoryLogger::log(
                $task,
                $task->pendingStatusTransition['from'],
                $task->pendingStatusTransition['to'],
            );

            $task->pendingStatusTransition = null;
        });
    }

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

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(AgentTaskQueue::class, 'task_id');
    }

    public function latestQueue(): HasOne
    {
        return $this->hasOne(AgentTaskQueue::class, 'task_id')->latest('created_at');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function activeInvoiceItem(): HasOne
    {
        return $this->hasOne(InvoiceItem::class)
            ->where('type', 'task')
            ->whereHas('invoice', function (Builder $query) {
                $query->where('status', '!=', 'cancelled')
                    ->whereNull('invoices.deleted_at');
            })
            ->latestOfMany();
    }

    public function invoiceOverride(): HasOne
    {
        return $this->hasOne(TaskInvoiceOverride::class);
    }

    public function scopeReadyToBill(Builder $query): Builder
    {
        return $query
            ->where('status', 'done')
            ->whereDoesntHave('invoiceItems', function (Builder $query) {
                $query->where('type', 'task')
                    ->whereHas('invoice', function (Builder $query) {
                        $query->where('status', '!=', 'cancelled')
                            ->whereNull('invoices.deleted_at');
                    });
            })
            ->whereDoesntHave('invoiceOverride');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }

    public function getInvoiceStatusAttribute(): string
    {
        if (! $this->relationLoaded('activeInvoiceItem')) {
            $this->load('activeInvoiceItem.invoice');
        }

        if (! $this->relationLoaded('invoiceOverride')) {
            $this->load('invoiceOverride');
        }

        $realStatus = 'not_invoiced';

        if ($this->activeInvoiceItem) {
            $this->activeInvoiceItem->loadMissing('invoice');
            $realStatus = $this->activeInvoiceItem->invoice?->payment_status === 'paid'
                ? 'paid'
                : 'invoiced';
        }

        $overrideStatus = $this->invoiceOverride?->status ?? 'not_invoiced';

        return $this->invoiceStatusRank($overrideStatus) > $this->invoiceStatusRank($realStatus)
            ? $overrideStatus
            : $realStatus;
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->with('author')->latest();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('sort_order');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(TaskChangeRequest::class)->latest();
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

    private function invoiceStatusRank(string $status): int
    {
        return match ($status) {
            'paid' => 2,
            'invoiced' => 1,
            default => 0,
        };
    }
}
