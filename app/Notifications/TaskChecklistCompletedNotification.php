<?php

namespace App\Notifications;

use App\Models\Task;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskChecklistCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'task_checklist_completed',
            'title'   => "All checklist items completed on {$this->task->title}",
            'body'    => "Project: {$this->task->project?->name}",
            'link'    => route('tasks.show', $this->task),
            'icon'    => 'check',
            'task_id' => $this->task->id,
        ];
    }

    public function toSlackText(): string
    {
        return "✅ *{$this->task->assignee?->name}* completed all checklist items on *{$this->task->title}*";
    }

    public function sendSlack(): void
    {
        SlackNotificationHelper::send($this->toSlackText());
    }
}
