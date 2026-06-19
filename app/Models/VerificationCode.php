<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'used',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'boolean',
    ];

    public function scopeValid($query, string $email)
    {
        return $query
            ->where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5)
            ->orderByDesc('created_at')
            ->limit(1);
    }
}
