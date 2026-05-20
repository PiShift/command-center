<?php

namespace App\Notifications;

use App\Models\Sprint;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class SprintCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Sprint $sprint,
        public readonly int    $doneCount,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'wantsEmailNotification') && $notifiable->wantsEmailNotification('sprint_completed')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'      => 'sprint_completed',
            'title'     => "Sprint completed: {$this->sprint->name}",
            'body'      => "{$this->doneCount} tasks done on {$this->sprint->project?->name}",
            'link'      => route('projects.show', $this->sprint->project),
            'icon'      => 'check',
            'sprint_id' => $this->sprint->id,
        ];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text("🏁 Sprint *{$this->sprint->name}* on _{$this->sprint->project?->name}_ completed — *{$this->doneCount}* tasks done");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        $channel = $this->sprint->project?->slack_channel ?: SlackNotificationHelper::defaultChannel();

        return SlackRoute::make($channel, SlackNotificationHelper::botToken());
    }
}
