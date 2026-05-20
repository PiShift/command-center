<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

    public function toSlackText(): string
    {
        return "🙋 *{$this->claimer->name}* claimed *{$this->task->title}* on _{$this->task->project?->name}_";
    }

    public function sendSlack(): void
    {
        SlackNotificationHelper::send($this->toSlackText());
    }
}
