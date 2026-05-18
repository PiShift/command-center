<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KanbanColumn extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'position',
        'is_protected',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'position'     => 'integer',
    ];

    // Auto-generate slug from name if not provided
    protected static function booted(): void
    {
        static::creating(function (self $column) {
            if (empty($column->slug)) {
                $base = Str::slug($column->name, '-');
                $slug = $base;
                $i    = 1;
                while (self::where('slug', $slug)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $column->slug = $slug;
            }
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status', 'slug');
    }

    /** All colour tokens supported in the kanban blade palette */
    public static function colorOptions(): array
    {
        return [
            'slate'   => 'Slate (grey)',
            'blue'    => 'Blue',
            'amber'   => 'Amber (orange)',
            'emerald' => 'Emerald (green)',
            'purple'  => 'Purple',
            'rose'    => 'Rose (pink)',
            'cyan'    => 'Cyan',
            'indigo'  => 'Indigo',
            'orange'  => 'Orange',
            'teal'    => 'Teal',
        ];
    }

    /** Resolve a colour slug to a hex value for inline styles. */
    public function getColorHexAttribute(): string
    {
        return match($this->color) {
            'blue'    => '#4a90d9',
            'amber'   => '#e07b39',
            'emerald' => '#3d9970',
            'purple'  => '#7b5ea7',
            'rose'    => '#e05a7a',
            'cyan'    => '#0891b2',
            'indigo'  => '#6366f1',
            'orange'  => '#D97757',
            'teal'    => '#0d9488',
            default   => '#8c8c8a', // slate / fallback
        };
    }
}
