<?php

namespace App\Console\Commands;

use App\Services\ExpenseService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringDrafts extends Command
{
    protected $signature   = 'expenses:generate-drafts {--month= : Month in Y-m format (default: current month)}';
    protected $description = 'Generate draft expenses for active recurring charges due this month';

    public function handle(ExpenseService $service): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $this->info("Generating recurring drafts for {$month->format('F Y')}…");

        $count = $service->generateRecurringDrafts($month);

        $this->info("Done — {$count} draft(s) created.");

        return self::SUCCESS;
    }
}
