<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplateItem extends Model
{
    protected $fillable = [
        'checklist_template_id',
        'label',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function taskChecklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class, 'checklist_template_item_id');
    }
}
