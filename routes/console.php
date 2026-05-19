<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate recurring expense drafts on the 1st of every month at 08:00
Schedule::command('expenses:generate-drafts')->monthlyOn(1, '08:00');

// Send scheduled notifications (overdue tasks, sprint deadlines, invoice reminders) daily at 08:00
Schedule::command('notifications:send-scheduled')->dailyAt('08:00');
