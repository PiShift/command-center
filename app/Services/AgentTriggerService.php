<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentTaskQueue;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Support\Str;

class AgentTriggerService
{
    /**
     * Trigger agent execution when a comment is posted on a task.
     *
     * Returns early if:
     * - Comment is from an agent (agent_id is set)
     * - Task has no agent assigned
     * - Task status is 'done'
     *
     * Otherwise:
     * - Cancels existing queued/dispatched entries
     * - Creates new queue entry with trigger comment info
     * - Wakes up the daemon
     */
    public static function triggerOnComment(Task $task, TaskComment $comment): void
    {
        // Don't trigger if: agent comment, task status is done, or no agent assigned
        if ($comment->agent_id || $task->status === 'done' || !$task->agent_id) {
            return;
        }

        // Cancel existing queued/dispatched entries for this task
        AgentTaskQueue::query()
            ->where('task_id', $task->id)
            ->whereIn('status', ['queued', 'dispatched'])
            ->delete();

        // Get agent and its runtime
        $agent = Agent::find($task->agent_id);
        if (!$agent || !$agent->runtime_id) {
            return;
        }

        // Create new queue entry
        $queueEntry = AgentTaskQueue::create([
            'task_id' => $task->id,
            'agent_id' => $agent->id,
            'runtime_id' => $agent->runtime_id,
            'team_id' => $task->project?->team_id ?? $agent->team_id,
            'status' => 'queued',
            'prompt' => AgentTaskQueue::buildPrompt($task),
            'trigger_comment_id' => $comment->id,
            'trigger_comment_content' => Str::limit($comment->body, 500),
        ]);

        // Wake up the daemon
        \App\WebSocket\WebSocketBroadcaster::wakeupDaemon($agent->runtime_id, $queueEntry->id);
    }
}
