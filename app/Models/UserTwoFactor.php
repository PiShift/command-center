<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserTwoFactor extends Model
{
    protected $table = 'user_two_factor';

    protected $fillable = [
        'user_id', 'secret', 'enabled', 'recovery_codes',
    ];

    protected $casts = [
        'enabled'        => 'boolean',
        'recovery_codes' => 'array',
        'secret'         => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate 8 recovery codes, store hashed, return plain for one-time display.
     */
    public function generateRecoveryCodes(): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(10));
            $plain[] = $code;
            $hashed[] = hash('sha256', $code);
        }

        $this->update(['recovery_codes' => $hashed]);

        return $plain;
    }
}
