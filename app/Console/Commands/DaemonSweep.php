<?php

namespace App\Console\Commands;

use App\Models\AgentRuntime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DaemonSweep extends Command
{
    protected $signature = 'daemon:sweep';

    protected $description = 'Mark stale daemon runtimes offline.';

    public function handle(): int
    {
        $count = AgentRuntime::query()
            ->online()
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', now()->subMinutes(3))
            ->update(['status' => 'offline']);

        Log::info('Daemon sweep completed.', ['offline_runtimes' => $count]);
        $this->info("Swept {$count} runtime(s).");

        return self::SUCCESS;
    }
}