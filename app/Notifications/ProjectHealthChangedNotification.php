<?php

namespace App\Notifications;

use App\Models\Project;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

        if ($notifiable->wantsEmailNotification('project_health')) {
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

    public function toSlackText(): string
    {
        return "🔴 *{$this->project->name}* health changed to *{$this->health}*";
    }

    public function sendSlack(): void
    {
        SlackNotificationHelper::send($this->toSlackText());
    }
}
