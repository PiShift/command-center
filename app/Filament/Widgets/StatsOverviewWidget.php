<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeProjects  = Project::where('status', 'active')->count();
        $blockedProjects = Project::where('health', 'blocked')->count();
        $atRiskProjects  = Project::where('health', 'at-risk')->count();
        $openTasks       = Task::where('status', '!=', 'done')->count();
        $inProgressTasks = Task::where('status', 'in-progress')->count();
        $overdueTasks    = Task::where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->count();
        $highPrioTasks   = Task::where('status', '!=', 'done')->where('priority', 'high')->count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $doneTasks       = Task::where('status', 'done')
            ->whereDate('completed_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Active Projects', $activeProjects)
                ->description($blockedProjects . ' blocked · ' . $atRiskProjects . ' at risk')
                ->descriptionColor($blockedProjects > 0 ? 'danger' : ($atRiskProjects > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-folder-open')
                ->color('info'),

            Stat::make('Open Tasks', $openTasks)
                ->description($inProgressTasks . ' in progress · ' . $highPrioTasks . ' high priority')
                ->descriptionColor($highPrioTasks > 0 ? 'warning' : 'info')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning'),

            Stat::make('Overdue Tasks', $overdueTasks)
                ->description($overdueTasks > 0 ? 'Need immediate attention' : 'All tasks on time')
                ->descriptionColor($overdueTasks > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),

            Stat::make('Done This Week', $doneTasks)
                ->description('Tasks completed in last 7 days')
                ->descriptionColor('success')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Active Customers', $activeCustomers)
                ->description(Customer::where('status', 'prospect')->count() . ' prospects · ' . Customer::where('status', 'churned')->count() . ' churned')
                ->icon('heroicon-o-users')
                ->color('gray'),
        ];
    }
}
