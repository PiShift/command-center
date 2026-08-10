<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklist extends Model
{
    protected $fillable = ['task_id', 'checklist_template_item_id', 'label', 'is_checked', 'sort_order'];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateItem::class, 'checklist_template_item_id');
    }

    /**
     * Template-sourced items are locked: they can be checked off but not deleted.
     */
    public function isLocked(): bool
    {
        return $this->checklist_template_item_id !== null;
    }
}
