<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TeamMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Team $team,
        public readonly User $member,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

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

    public function toSlackText(): string
    {
        return "👥 *{$this->member->name}* was added to team *{$this->team->name}*";
    }

    public function sendSlack(): void
    {
        SlackNotificationHelper::send($this->toSlackText());
    }
}
