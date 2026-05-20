<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class TaskClaimedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly User $claimer,
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
            'type'    => 'task_claimed',
            'title'   => "{$this->claimer->name} claimed a task: {$this->task->title}",
            'body'    => "On project {$this->task->project?->name}",
            'link'    => route('tasks.show', $this->task),
            'icon'    => 'clipboard',
            'task_id' => $this->task->id,
        ];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->content("🙋 *{$this->claimer->name}* claimed *{$this->task->title}* on _{$this->task->project?->name}_");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::webhookUrl());
    }
}
