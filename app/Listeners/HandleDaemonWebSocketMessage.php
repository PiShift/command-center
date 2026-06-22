<?php

namespace App\Listeners;

use App\Models\AgentRuntime;
use Laravel\Reverb\Events\MessageReceived;

class HandleDaemonWebSocketMessage
{
    public function handle(MessageReceived $event): void
    {
        $raw = $event->message;
        $data = json_decode($raw, true);

        if (! $data || ! isset($data['type'])) {
            return;
        }

        if ($data['type'] === 'daemon:heartbeat') {
            $runtimeId = $data['payload']['runtime_id'] ?? null;

            if ($runtimeId) {
                AgentRuntime::where('id', $runtimeId)->update([
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);
            }
        }
    }
}