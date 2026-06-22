<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class AgentTaskCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $taskId,
        public string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('tasks.' . $this->taskId)];
    }

    public function broadcastAs(): string
    {
        return 'agent.completed';
    }
}
