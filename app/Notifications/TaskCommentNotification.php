<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class TaskCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task        $task,
        public readonly TaskComment $comment,
        public readonly User        $commenter,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmailNotification('task_comment')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        $preview = mb_substr($this->comment->body, 0, 100);
        return [
            'type'       => 'task_comment',
            'title'      => "{$this->commenter->name} commented on {$this->task->title}",
            'body'       => $preview,
            'link'       => route('tasks.show', $this->task),
            'icon'       => 'clipboard',
            'task_id'    => $this->task->id,
            'comment_id' => $this->comment->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New comment on task: {$this->task->title}")
            ->view('emails.notifications.task-comment', [
                'task'      => $this->task,
                'comment'   => $this->comment,
                'commenter' => $this->commenter,
                'user'      => $notifiable,
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $preview = mb_substr($this->comment->body, 0, 80);

        return (new SlackMessage)
            ->text("💬 *{$this->commenter->name}* commented on *{$this->task->title}*: \"{$preview}\"");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        $channel = $this->task->project?->slack_channel ?: SlackNotificationHelper::defaultChannel();

        return SlackRoute::make($channel, SlackNotificationHelper::botToken());
    }
}
