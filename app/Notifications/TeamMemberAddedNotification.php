<?php

namespace App\Notifications;

use App\Models\Team;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class TeamMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Team $team,
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
            'type'    => 'team_member_added',
            'title'   => "You were added to team: {$this->team->name}",
            'body'    => "You are now a member of {$this->team->name}",
            'link'    => route('teams.show', $this->team),
            'icon'    => 'users',
            'team_id' => $this->team->id,
        ];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->content("👥 *{$notifiable->name}* was added to team *{$this->team->name}*");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::webhookUrl());
    }
}
