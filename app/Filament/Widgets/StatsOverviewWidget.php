<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Projects', Project::where('status', 'active')->count())
                ->color('info'),
            Stat::make('Open Tasks', Task::where('status', '!=', 'done')->count())
                ->color('warning'),
            Stat::make('Customers', Customer::count())
                ->color('gray'),
            Stat::make('Tasks Done', Task::where('status', 'done')->count())
                ->color('success'),
        ];
    }
}
