<?php

namespace App\Notifications;

use App\Models\Sprint;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class SprintPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Sprint $sprint,
        public readonly int    $taskCount,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmailNotification('sprint_published')) {
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
            'type'      => 'sprint_published',
            'title'     => "Sprint published: {$this->sprint->name}",
            'body'      => "{$this->taskCount} tasks now active on {$this->sprint->project?->name}",
            'link'      => route('projects.show', $this->sprint->project),
            'icon'      => 'play',
            'sprint_id' => $this->sprint->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Sprint published: {$this->sprint->name}")
            ->view('emails.notifications.sprint-published', [
                'sprint'    => $this->sprint,
                'taskCount' => $this->taskCount,
                'user'      => $notifiable,
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->content("🚀 Sprint *{$this->sprint->name}* is now active on _{$this->sprint->project?->name}_ — *{$this->taskCount}* tasks available");
    }

    public function routeNotificationForSlack(): string
    {
        return SlackNotificationHelper::webhookUrl();
    }
}
