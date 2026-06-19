<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PersonalAccessToken extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'token_hash',
        'token_prefix',
        'expires_at',
        'last_used_at',
        'revoked',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
        'revoked'      => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $token): void {
            if (empty($token->id)) {
                $token->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query
            ->where('revoked', false)
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
