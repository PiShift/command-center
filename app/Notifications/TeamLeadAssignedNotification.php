<?php

namespace App\Notifications;

use App\Models\Team;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class TeamLeadAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Team $team,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmailNotification('team_lead_assigned')) {
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
            'type'    => 'team_lead_assigned',
            'title'   => "You are now the lead of {$this->team->name}",
            'body'    => "You have been assigned as team lead",
            'link'    => route('teams.show', $this->team),
            'icon'    => 'users',
            'team_id' => $this->team->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You are now the lead of {$this->team->name}")
            ->view('emails.notifications.team-lead-assigned', [
                'team' => $this->team,
                'user' => $notifiable,
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text("⭐ *{$notifiable->name}* is now the lead of *{$this->team->name}*");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::webhookUrl());
    }
}
