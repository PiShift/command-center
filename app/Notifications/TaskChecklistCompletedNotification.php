<?php

namespace App\Notifications;

use App\Models\Task;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class TaskChecklistCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (SlackNotificationHelper::isEnabled()) {
            $channels[] = 'slack';
        }

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

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text("✅ *{$notifiable->name}* completed all checklist items on *{$this->task->title}*");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::webhookUrl());
    }
}
