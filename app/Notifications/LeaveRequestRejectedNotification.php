<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestRejectedNotification extends Notification implements ShouldQueue
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
            'type' => 'leave_request_rejected',
            'title' => 'Your leave request was rejected',
            'body' => $this->leaveRequest->rejection_reason ?: 'No reason provided',
            'link' => route('employees.show', $this->leaveRequest->employee) . '#leaves',
            'icon' => 'x-circle',
            'leave_request_id' => $this->leaveRequest->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your leave request was rejected')
            ->line('Your leave request from ' . $this->leaveRequest->start_date?->format('d M Y') . ' to ' . $this->leaveRequest->end_date?->format('d M Y') . ' has been rejected.')
            ->line('Reason: ' . ($this->leaveRequest->rejection_reason ?: 'No reason provided'))
            ->action('View Leave', route('employees.show', $this->leaveRequest->employee) . '#leaves');
    }
}
