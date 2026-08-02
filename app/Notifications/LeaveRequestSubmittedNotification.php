<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class LeaveRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'leave_request_submitted',
            'title' => $this->leaveRequest->employee?->display_name . ' submitted a leave request',
            'body' => $this->leaveRequest->working_days . ' days (' . $this->leaveRequest->start_date?->format('d M Y') . ' to ' . $this->leaveRequest->end_date?->format('d M Y') . ')',
            'link' => route('leaves.index'),
            'icon' => 'calendar',
            'leave_request_id' => $this->leaveRequest->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->leaveRequest->employee?->display_name . ' submitted a leave request')
            ->line($this->leaveRequest->employee?->display_name . ' submitted a leave request for ' . $this->leaveRequest->working_days . ' days.')
            ->line($this->leaveRequest->start_date?->format('d M Y') . ' to ' . $this->leaveRequest->end_date?->format('d M Y'))
            ->action('Open Leave Management', route('leaves.index'));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text($this->leaveRequest->employee?->display_name . ' submitted a leave request for ' . $this->leaveRequest->working_days . ' days (' . $this->leaveRequest->start_date?->format('d M Y') . ' to ' . $this->leaveRequest->end_date?->format('d M Y') . ')');
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::defaultChannel(), SlackNotificationHelper::botToken());
    }
}
