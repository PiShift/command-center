<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id', 'name', 'browser', 'platform', 'ip_address',
        'token', 'trusted', 'last_used_at',
    ];

    protected $casts = [
        'trusted'      => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find a device by comparing the plain token from the cookie
     * against stored hashed tokens for a given user.
     */
    public static function findByToken(int $userId, string $plainToken): ?self
    {
        $hashed = hash('sha256', $plainToken);
        return self::where('user_id', $userId)->where('token', $hashed)->first();
    }
}
