<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    protected $fillable = ['name', 'slug', 'group', 'description', 'depends_on'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'depends_on');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Permission::class, 'depends_on');
    }

    /**
     * All ancestor slugs this permission requires (recursive).
     */
    public function ancestorSlugs(): array
    {
        $slugs = [];
        $current = $this->parent;
        while ($current) {
            $slugs[] = $current->slug;
            $current = $current->parent;
        }
        return $slugs;
    }

    /**
     * All descendant slugs that depend on this permission (recursive).
     */
    public function descendantSlugs(): array
    {
        $slugs = [];
        foreach ($this->children as $child) {
            $slugs[] = $child->slug;
            array_push($slugs, ...$child->descendantSlugs());
        }
        return $slugs;
    }
}
