<?php

namespace App\Notifications;

use App\Models\Task;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly int  $daysOverdue,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmailNotification('task_overdue')) {
            $channels[] = 'mail';
        }

        if (SlackNotificationHelper::isEnabled()) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'task_overdue',
            'title'   => "Task overdue: {$this->task->title}",
            'body'    => "This task is {$this->daysOverdue} day(s) overdue on {$this->task->project?->name}",
            'link'    => route('tasks.show', $this->task),
            'icon'    => 'clipboard',
            'task_id' => $this->task->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task overdue: {$this->task->title}")
            ->view('emails.notifications.task-overdue', [
                'task'        => $this->task,
                'daysOverdue' => $this->daysOverdue,
                'user'        => $notifiable,
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->content("⚠️ *{$this->task->title}* is *{$this->daysOverdue} day(s) overdue* — assigned to *{$notifiable->name}* on _{$this->task->project?->name}_");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::webhookUrl());
    }
}
