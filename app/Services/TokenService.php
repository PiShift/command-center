<?php

namespace App\Services;

class TokenService
{
    public function generatePAT(): array
    {
        $hex = bin2hex(random_bytes(20));
        $raw = 'mul_' . $hex;

        return [
            'raw'    => $raw,
            'hash'   => $this->hashToken($raw),
            'prefix' => substr($raw, 0, 12),
        ];
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function generateOTP(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
