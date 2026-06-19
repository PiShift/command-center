<?php

namespace App\Console\Commands;

use App\Models\DaemonToken;
use App\Models\User;
use App\Services\DaemonTokenService;
use Illuminate\Console\Command;

class DaemonTokenCreate extends Command
{
    protected $signature = 'daemon:token:create {user_id} {name}';

    protected $description = 'Create a daemon token for a user.';

    public function handle(DaemonTokenService $service): int
    {
        $user = User::findOrFail((int) $this->argument('user_id'));
        $generated = $service->generate();

        DaemonToken::create([
            'user_id'    => $user->id,
            'token_hash' => $generated['hash'],
            'name'       => (string) $this->argument('name'),
        ]);

        $this->line($generated['raw']);

        return self::SUCCESS;
    }
}