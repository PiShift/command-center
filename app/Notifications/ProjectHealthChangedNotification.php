<?php

namespace App\Notifications;

use App\Models\Project;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class ProjectHealthChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Project $project,
        public readonly string  $health,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'wantsEmailNotification') && $notifiable->wantsEmailNotification('project_health')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'project_health',
            'title'      => "Project health alert: {$this->project->name}",
            'body'       => "Health changed to {$this->health}",
            'link'       => route('projects.show', $this->project),
            'icon'       => 'folder',
            'project_id' => $this->project->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Project health alert: {$this->project->name}")
            ->view('emails.notifications.project-health', [
                'project' => $this->project,
                'health'  => $this->health,
                'user'    => $notifiable,
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text("🔴 *{$this->project->name}* health changed to *{$this->health}*");
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::defaultChannel(), SlackNotificationHelper::botToken());
    }
}
