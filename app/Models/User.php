<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'color',
        'initials',
        'notification_preferences',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')->withTimestamps();
    }

    public function twoFactor(): HasOne
    {
        return $this->hasOne(UserTwoFactor::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function loginHistory(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class)->orderByDesc('created_at');
    }

    public function hasPermission(string $slug): bool
    {
        $role = $this->relationLoaded('roleModel') ? $this->roleModel : $this->roleModel()->with('permissions')->first();

        if (! $role) {
            return false;
        }

        if ($role->isSuperAdmin()) {
            return true;
        }

        return $role->permissions->contains('slug', $slug);
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactor?->enabled === true;
    }

    public function requiresTwoFactor(): bool
    {
        $slug = $this->roleModel?->slug;
        return in_array($slug, ['super-admin', 'manager']);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'notification_preferences' => 'array',
        ];
    }
}

