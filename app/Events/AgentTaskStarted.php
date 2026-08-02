<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class AgentTaskStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $taskId,
        public string $queueId,
        public string $agentName,
        public string $provider,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('tasks.' . $this->taskId),
            new Channel('agent-activity'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.started';
    }
}
