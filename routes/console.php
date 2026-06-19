<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\LeaveService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate recurring expense drafts on the 1st of every month at 08:00
Schedule::command('expenses:generate-drafts')->monthlyOn(1, '08:00');

// Accrue monthly leave balances on the 1st of each month
Schedule::call(fn () => app(LeaveService::class)->accrueMonthlyLeave(now()))->monthlyOn(1, '00:30');

// Carry remaining annual leave into the next year shortly after year-end
Schedule::call(fn () => app(LeaveService::class)->carryOverBalances(now()->subYear()->year))->yearlyOn(1, 1, '01:00');

// Sweep stale daemon runtimes
Schedule::command('daemon:sweep')->everyTwoMinutes();

// Send scheduled notifications (overdue tasks, sprint deadlines, invoice reminders) daily at 08:00
Schedule::command('notifications:send-scheduled')->dailyAt('08:00');
