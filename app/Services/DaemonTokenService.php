<?php

namespace App\Services;

class DaemonTokenService
{
    public function generate(): array
    {
        $raw = 'mdt_' . bin2hex(random_bytes(20));

        return [
            'raw'  => $raw,
            'hash' => $this->hash($raw),
        ];
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}