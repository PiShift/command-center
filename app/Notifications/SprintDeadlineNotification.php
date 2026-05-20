<?php

namespace App\Notifications;

use App\Models\Sprint;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SprintDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Sprint $sprint,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmailNotification('sprint_deadline')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'      => 'sprint_deadline',
            'title'     => "Sprint deadline in 3 days: {$this->sprint->name}",
            'body'      => "Deadline: {$this->sprint->deadline?->format('M d, Y')} — Project: {$this->sprint->project?->name}",
            'link'      => route('projects.show', $this->sprint->project),
            'icon'      => 'play',
            'sprint_id' => $this->sprint->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Sprint deadline approaching: {$this->sprint->name}")
            ->view('emails.notifications.sprint-deadline', [
                'sprint' => $this->sprint,
                'user'   => $notifiable,
            ]);
    }

    public function toSlackText(): string
    {
        return "⏰ Sprint *{$this->sprint->name}* on _{$this->sprint->project?->name}_ deadline is in *3 days* ({$this->sprint->deadline?->format('M d, Y')})";
    }

    public function sendSlack(): void
    {
        SlackNotificationHelper::send($this->toSlackText());
    }
}
