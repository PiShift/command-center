<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task   $task,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly User   $changer,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmailNotification('task_status_changed')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'task_status_changed',
            'title'   => "Task status changed: {$this->task->title}",
            'body'    => "Moved from {$this->oldStatus} to {$this->newStatus} by {$this->changer->name}",
            'link'    => route('tasks.show', $this->task),
            'icon'    => 'clipboard',
            'task_id' => $this->task->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task status changed: {$this->task->title}")
            ->view('emails.notifications.task-status-changed', [
                'task'      => $this->task,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'changer'   => $this->changer,
                'user'      => $notifiable,
            ]);
    }

    public function toSlackText(): string
    {
        return "🔄 *{$this->task->title}* moved from _{$this->oldStatus}_ to _{$this->newStatus}_ by *{$this->changer->name}*";
    }

    public function sendSlack(): void
    {
        SlackNotificationHelper::send($this->toSlackText());
    }
}
