<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestApprovedNotification extends Notification implements ShouldQueue
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
            'type' => 'leave_request_approved',
            'title' => 'Your leave request was approved',
            'body' => $this->leaveRequest->start_date?->format('d M Y') . ' to ' . $this->leaveRequest->end_date?->format('d M Y'),
            'link' => route('employees.show', $this->leaveRequest->employee) . '#leaves',
            'icon' => 'check-circle',
            'leave_request_id' => $this->leaveRequest->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your leave request was approved')
            ->line('Your leave request from ' . $this->leaveRequest->start_date?->format('d M Y') . ' to ' . $this->leaveRequest->end_date?->format('d M Y') . ' has been approved.')
            ->action('View Leave', route('employees.show', $this->leaveRequest->employee) . '#leaves');
    }
}
