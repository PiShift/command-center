<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmployeeDocument extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'title',
        'type',
        'notes',
    ];

    // ── Relationships ────────────────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    // ── Media ────────────────────────────────────────────────────────────────
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
            ->withResponsiveImages();
    }

    public function registerMediaConversions(?Media $media = null): void {}
}
