<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectResource extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'resource_type',
        'resource_ref',
        'label',
        'position',
        'created_by',
    ];

    protected $casts = [
        'resource_ref' => 'array',
        'position'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $resource): void {
            if (empty($resource->id)) {
                $resource->id = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isGithubRepo(): bool
    {
        return $this->resource_type === 'github_repo';
    }

    public function isLocalDirectory(): bool
    {
        return $this->resource_type === 'local_directory';
    }

    public function getLocalPath(): ?string
    {
        $path = $this->resource_ref['local_path'] ?? null;

        return is_string($path) && trim($path) !== '' ? trim($path) : null;
    }

    public function getDaemonId(): ?string
    {
        $daemonId = $this->resource_ref['daemon_id'] ?? null;

        return is_string($daemonId) && trim($daemonId) !== '' ? trim($daemonId) : null;
    }

    public function getUrl(): ?string
    {
        $url = $this->resource_ref['url'] ?? null;

        return is_string($url) && trim($url) !== '' ? trim($url) : null;
    }
}
