<?php

namespace App\Console\Commands;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Console\Command;

class DaemonTokenCreate extends Command
{
    protected $signature = 'daemon:token:create {user_id} {name}';

    protected $description = 'Create a personal access token for a user.';

    public function handle(TokenService $service): int
    {
        $user = User::findOrFail((int) $this->argument('user_id'));
        $generated = $service->generatePAT();

        PersonalAccessToken::create([
            'user_id'    => $user->id,
            'name'       => (string) $this->argument('name'),
            'token_hash' => $generated['hash'],
            'token_prefix' => $generated['prefix'],
        ]);

        $this->line($generated['raw']);

        return self::SUCCESS;
    }
}