<?php

namespace App\Console\Commands;

use App\Mail\InvoiceOverdueMailable;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Sprint;
use App\Models\Task;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\SprintDeadlineNotification;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledNotifications extends Command
{
    protected $signature   = 'notifications:send-scheduled';
    protected $description = 'Send scheduled notifications: overdue tasks, sprint deadlines, invoice reminders';

    public function handle(): void
    {
        $this->sendOverdueTaskNotifications();
        $this->sendSprintDeadlineNotifications();
        $this->sendOverdueInvoiceEmails();
        $this->sendInvoiceReminderEmails();
    }

    private function sendOverdueTaskNotifications(): void
    {
        $cutoff = now()->subHours(24);

        Task::with(['assignee', 'project'])
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'done')
            ->where(fn ($q) => $q->whereNull('overdue_notified_at')->orWhere('overdue_notified_at', '<=', $cutoff))
            ->chunk(50, function ($tasks) {
                foreach ($tasks as $task) {
                    if (! $task->assignee) {
                        continue;
                    }

                    $daysOverdue = (int) now()->diffInDays($task->due_date);

                    $task->assignee->notify(new TaskOverdueNotification($task, $daysOverdue));
                    $task->update(['overdue_notified_at' => now()]);
                }
            });

        $this->info('Overdue task notifications sent.');
    }

    private function sendSprintDeadlineNotifications(): void
    {
        $targetDate = now()->addDays(3)->toDateString();

        Sprint::with(['project', 'tasks.assignee'])
            ->where('status', 'active')
            ->whereDate('deadline', $targetDate)
            ->chunk(20, function ($sprints) {
                foreach ($sprints as $sprint) {
                    $assigneeIds = $sprint->tasks->pluck('assigned_to')->filter()->unique();

                    $assigneeIds->each(function ($userId) use ($sprint) {
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $user->notify(new SprintDeadlineNotification($sprint));
                        }
                    });

                    SlackNotificationHelper::notifyOnce(new SprintDeadlineNotification($sprint));
                }
            });

        $this->info('Sprint deadline notifications sent.');
    }

    private function sendOverdueInvoiceEmails(): void
    {
        Invoice::with(['customer', 'items'])
            ->where('status', 'published')
            ->where('payment_status', '!=', 'paid')
            ->whereDate('due_date', now()->toDateString())
            ->chunk(50, function ($invoices) {
                foreach ($invoices as $invoice) {
                    if ($invoice->customer?->email) {
                        Mail::to($invoice->customer->email)
                            ->queue(new InvoiceOverdueMailable($invoice));
                    }
                }
            });

        $this->info('Invoice overdue emails sent.');
    }

    private function sendInvoiceReminderEmails(): void
    {
        InvoiceReminder::with(['invoice.customer', 'invoice.items'])
            ->where('sent', false)
            ->whereDate('scheduled_date', now()->toDateString())
            ->chunk(50, function ($reminders) {
                foreach ($reminders as $reminder) {
                    $invoice = $reminder->invoice;

                    if (! $invoice || $invoice->payment_status === 'paid') {
                        $reminder->update(['sent' => true, 'sent_at' => now()]);
                        continue;
                    }

                    if ($invoice->customer?->email) {
                        Mail::to($invoice->customer->email)
                            ->queue(new InvoiceOverdueMailable($invoice));
                    }

                    $reminder->update(['sent' => true, 'sent_at' => now()]);
                }
            });

        $this->info('Invoice reminder emails sent.');
    }
}
