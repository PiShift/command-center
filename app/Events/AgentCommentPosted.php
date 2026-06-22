<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class AgentCommentPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $taskId,
        public string $body,
        public string $authorName,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('tasks.' . $this->taskId)];
    }

    public function broadcastAs(): string
    {
        return 'agent.comment';
    }
}
