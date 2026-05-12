<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'website',
        'status',
        'industry',
        'avatar_url',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function activeTasks(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Project::class)
            ->where('tasks.status', '!=', 'done');
    }
}
