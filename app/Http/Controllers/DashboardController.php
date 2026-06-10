<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CompanyBankAccount;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        if (auth()->user()?->roleModel?->slug === 'developer') {
            return redirect()->route('board');
        }

        // Fix 4 — Date range from request
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year',  now()->year);
        $month = max(1, min(12, $month));
        $year  = max(2020, min((int) now()->addYear()->year, $year));

        $selectedStart = Carbon::create($year, $month)->startOfMonth();
        $selectedEnd   = Carbon::create($year, $month)->endOfMonth();
        $prevStart     = $selectedStart->copy()->subMonth()->startOfMonth();
        $prevEnd       = $selectedStart->copy()->subMonth()->endOfMonth();
        $periodLabel   = $selectedStart->format('F Y');

        $data = Cache::tags(['dashboard'])->remember("dashboard.{$year}.{$month}", 300,
            function () use ($selectedStart, $selectedEnd, $prevStart, $prevEnd, $year, $month) {

            $now = now();

            // ── Financial ────────────────────────────────────────────────────

            $revenueThisMonth = InvoicePayment::whereBetween('payment_date', [$selectedStart, $selectedEnd])
                ->sum('amount');

            $revenueLastMonth = InvoicePayment::whereBetween('payment_date', [$prevStart, $prevEnd])
                ->sum('amount');

            $revenueGrowth = $revenueLastMonth > 0
                ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                : ($revenueThisMonth > 0 ? 100 : 0);

            $outstandingAmount = Invoice::where('status', 'published')
                ->whereNotIn('payment_status', ['paid'])
                ->sum(DB::raw('total - amount_paid'));

            $outstandingInvoicesCount = Invoice::where('status', 'published')
                ->whereNotIn('payment_status', ['paid'])
                ->count();

            $overdueInvoices = Invoice::where('status', 'published')
                ->whereNotIn('payment_status', ['paid'])
                ->whereDate('due_date', '<', $now->toDateString())
                ->get(['id', 'total', 'amount_paid']);

            $overdueInvoicesAmount = $overdueInvoices->sum(fn($i) => $i->total - $i->amount_paid);
            $overdueInvoicesCount  = $overdueInvoices->count();

            $expensesThisMonth = Expense::confirmed()
                ->whereBetween('expense_date', [$selectedStart, $selectedEnd])
                ->sum('amount');

            $expensesLastMonth = Expense::confirmed()
                ->whereBetween('expense_date', [$prevStart, $prevEnd])
                ->sum('amount');

            $netThisMonth = $revenueThisMonth - $expensesThisMonth;

            // Revenue by month — 6 months ending at selected month
            $revenueByMonth  = [];
            $expensesByMonth = [];
            for ($i = 5; $i >= 0; $i--) {
                $m     = $selectedEnd->copy()->subMonths($i);
                $label = $m->format('M Y');
                $revenueByMonth[$label] = InvoicePayment::whereBetween('payment_date', [
                    $m->copy()->startOfMonth(), $m->copy()->endOfMonth(),
                ])->sum('amount');
                $expensesByMonth[$label] = Expense::confirmed()->whereBetween('expense_date', [
                    $m->copy()->startOfMonth(), $m->copy()->endOfMonth(),
                ])->sum('amount');
            }

            // Revenue by customer — top 5 in selected year
            $revenueByCustomer = InvoicePayment::join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
                ->join('customers', 'invoices.customer_id', '=', 'customers.id')
                ->whereYear('invoice_payments.payment_date', $year)
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('total_revenue')
                ->limit(5)
                ->get([
                    'customers.id as customer_id',
                    'customers.name as customer_name',
                    DB::raw('SUM(invoice_payments.amount) as total_revenue'),
                ])
                ->pluck('total_revenue', 'customer_name')
                ->toArray();

            // ── Projects ─────────────────────────────────────────────────────

            $projectCounts = [
                'active'   => Project::where('status', 'active')->count(),
                'paused'   => Project::where('status', 'paused')->count(),
                'complete' => Project::where('status', 'complete')->count(),
                'total'    => Project::count(),
            ];
            $projectCounts['active_non_complete'] = $projectCounts['active'] + $projectCounts['paused'];

            $projectsByHealth = [
                'on_track' => Project::where('health', 'on-track')->where('status', 'active')->count(),
                'at_risk'  => Project::where('health', 'at-risk')->where('status', 'active')->count(),
                'blocked'  => Project::where('health', 'blocked')->where('status', 'active')->count(),
            ];

            $activeProjectsList = Project::where('status', 'active')
                ->with(['customer', 'sprints' => fn($q) => $q->where('status', 'active')->with(['tasks'])])
                ->withCount([
                    'tasks as total_tasks_count',
                    'tasks as open_tasks_count'     => fn($q) => $q->whereIn('status', ['open', 'todo']),
                    'tasks as progress_tasks_count'  => fn($q) => $q->where('status', 'in-progress'),
                    'tasks as done_tasks_count'      => fn($q) => $q->where('status', 'done'),
                ])
                ->orderByRaw("CASE health WHEN 'blocked' THEN 1 WHEN 'at-risk' THEN 2 WHEN 'on-track' THEN 3 ELSE 4 END")
                ->get();

            $activeProjects = $activeProjectsList->map(function ($p) {
                $activeSprint = $p->sprints->first();
                $progressPct  = $p->total_tasks_count > 0
                    ? round(($p->done_tasks_count / $p->total_tasks_count) * 100)
                    : 0;
                return [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'health'           => $p->health,
                    'status'           => $p->status,
                    'customer_name'    => $p->customer?->name,
                    'sprint_name'      => $activeSprint?->name,
                    'sprint_deadline'  => $activeSprint?->deadline,
                    'sprint_days_left' => $activeSprint?->deadline ? now()->diffInDays($activeSprint->deadline, false) : null,
                    'open'             => $p->open_tasks_count,
                    'in_progress'      => $p->progress_tasks_count,
                    'done'             => $p->done_tasks_count,
                    'total'            => $p->total_tasks_count,
                    'progress_pct'     => $progressPct,
                ];
            })->toArray();

            $sprintDeadlines = Sprint::where('status', 'active')
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [$now->toDateString(), $now->copy()->addDays(14)->toDateString()])
                ->with(['project', 'tasks'])
                ->orderBy('deadline')
                ->get()
                ->map(function ($s) use ($now) {
                    $total = $s->tasks->count();
                    $done  = $s->tasks->where('status', 'done')->count();
                    return [
                        'sprint_name'  => $s->name,
                        'project_name' => $s->project?->name,
                        'project_id'   => $s->project_id,
                        'deadline'     => $s->deadline,
                        'days_left'    => $now->diffInDays($s->deadline, false),
                        'progress_pct' => $total > 0 ? round(($done / $total) * 100) : 0,
                    ];
                })
                ->toArray();

            // ── Tasks ────────────────────────────────────────────────────────

            $tasksByStatus = Task::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $tasksPipeline = [
                'todo'        => $tasksByStatus['todo'] ?? 0,
                'in-progress' => $tasksByStatus['in-progress'] ?? 0,
                'in-review'   => $tasksByStatus['in-review'] ?? 0,
            ];
            $tasksOpenCount = $tasksByStatus['open'] ?? 0;

            $tasksByPriority = Task::select('priority', DB::raw('count(*) as count'))
                ->whereNotIn('status', ['done'])
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray();

            $tasksByType = Task::select('type', DB::raw('count(*) as count'))
                ->whereNotIn('status', ['done'])
                ->whereNotNull('type')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            // Tasks completed per day in selected month (30 days ending at period end)
            $periodStart30 = $selectedEnd->copy()->subDays(29)->startOfDay();
            $tasksCompletedByDay = Task::where('status', 'done')
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $periodStart30)
                ->where('completed_at', '<=', $selectedEnd->copy()->endOfDay())
                ->select(DB::raw('DATE(completed_at) as day'), DB::raw('count(*) as count'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $tasksCompletedFilled = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = $selectedEnd->copy()->subDays($i)->toDateString();
                $tasksCompletedFilled[$day] = $tasksCompletedByDay[$day] ?? 0;
            }

            $overdueTasksCount = Task::whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $now->toDateString())
                ->count();

            $overdueTasksList = Task::with(['project', 'assignee'])
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $now->toDateString())
                ->orderBy('due_date')
                ->limit(10)
                ->get()
                ->map(fn($t) => [
                    'id'           => $t->id,
                    'title'        => $t->title,
                    'project_name' => $t->project?->name,
                    'project_id'   => $t->project_id,
                    'assignee'     => $t->assignee?->name,
                    'days_overdue' => now()->diffInDays($t->due_date),
                ])
                ->toArray();

            $unclaimedTasksCount   = Task::whereIn('status', ['open', 'todo'])->whereNull('assigned_to')->count();
            $highPriorityOpenCount = Task::whereNotIn('status', ['done'])->where('priority', 'high')->count();

            // ── Team / People (Fix 2: developers only) ───────────────────────

            $developers = User::whereHas('roleModel', fn($q) => $q->where('slug', 'developer'))
                ->get(['id', 'name', 'initials', 'color']);

            $thisMonthDone = Task::where('status', 'done')
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$selectedStart, $selectedEnd])
                ->whereNotNull('assigned_to')
                ->select('assigned_to', DB::raw('count(*) as count'), DB::raw('SUM(weight) as total_weight'))
                ->groupBy('assigned_to')
                ->get()
                ->keyBy('assigned_to');

            $inProgressTasks = Task::whereIn('status', ['in-progress', 'in-review'])
                ->whereNotNull('assigned_to')
                ->select('assigned_to', DB::raw('count(*) as count'))
                ->groupBy('assigned_to')
                ->pluck('count', 'assigned_to')
                ->toArray();

            $activeTasks = Task::whereNotIn('status', ['done'])
                ->whereNotNull('assigned_to')
                ->select('assigned_to', DB::raw('count(*) as count'))
                ->groupBy('assigned_to')
                ->pluck('count', 'assigned_to')
                ->toArray();

            $teamPerformance = $developers->map(function ($u) use ($thisMonthDone, $inProgressTasks, $activeTasks) {
                $done = $thisMonthDone->get($u->id);
                return [
                    'id'               => $u->id,
                    'name'             => $u->name,
                    'initials'         => $u->initials ?? strtoupper(substr($u->name, 0, 2)),
                    'color'            => $u->color ?? '#D97757',
                    'completed_month'  => $done?->count ?? 0,
                    'in_progress'      => $inProgressTasks[$u->id] ?? 0,
                    'active_total'     => $activeTasks[$u->id] ?? 0,
                    'weight_completed' => $done?->total_weight ?? 0,
                ];
            })
            ->sortByDesc('completed_month')
            ->values()
            ->toArray();

            $topPerformers = array_slice($teamPerformance, 0, 3);

            $teamWorkload = collect($teamPerformance)->map(fn($m) => [
                'name'         => $m['name'],
                'initials'     => $m['initials'],
                'color'        => $m['color'],
                'active_tasks' => $m['active_total'],
            ])->sortByDesc('active_tasks')->values()->toArray();

            // ── Customers ─────────────────────────────────────────────────────

            $customerCounts = [
                'prospect' => Customer::where('status', 'prospect')->count(),
                'active'   => Customer::where('status', 'active')->count(),
                'churned'  => Customer::where('status', 'churned')->count(),
                'total'    => Customer::count(),
            ];

            $activeCustomersCount = Customer::whereHas('projects', fn($q) => $q->where('status', 'active'))->count();

            // ── Collection Rate ───────────────────────────────────────────────

            $totalInvoicedThisYear  = Invoice::whereYear('created_at', $year)->sum('total');
            $totalCollectedThisYear = InvoicePayment::whereYear('payment_date', $year)->sum('amount');
            $collectionRate = $totalInvoicedThisYear > 0
                ? round($totalCollectedThisYear / $totalInvoicedThisYear * 100, 1)
                : 0;

            // ── Available Credits ─────────────────────────────────────────────

            $availableCreditsSum = DB::table('customer_credits')
                ->whereIn('status', ['available', 'partially_used'])
                ->sum('amount_remaining');

            $customersWithCredit = DB::table('customer_credits')
                ->whereIn('status', ['available', 'partially_used'])
                ->distinct('customer_id')
                ->count('customer_id');

            // ── Bank Accounts ────────────────────────────────────────────────

            $bankAccounts = CompanyBankAccount::query()
                ->withSum('invoicePayments as payments_in_total', 'amount')
                ->withSum(['expenses as expenses_out_total' => fn($q) => $q->where('status', 'confirmed')], 'amount')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
                ->map(function (CompanyBankAccount $account) {
                    $account->computed_balance = (float) ($account->payments_in_total ?? 0) - (float) ($account->expenses_out_total ?? 0);
                    return $account;
                });

            $totalPendingAdvances = (float) EmployeeAdvance::pending()->sum('amount');

            $activeLoans = EmployeeLoan::active()
                ->withSum('repayments as repaid_total', 'amount')
                ->get(['id', 'amount_total']);

            $totalActiveLoansBalance = (float) $activeLoans->sum(function (EmployeeLoan $loan) {
                return max(0, (float) $loan->amount_total - (float) ($loan->repaid_total ?? 0));
            });

            $lastPayrollRun = PayrollRun::query()
                ->where('status', 'paid')
                ->orderByDesc('month')
                ->first();

            $pendingPayrollRuns = PayrollRun::query()
                ->whereIn('status', ['draft', 'approved'])
                ->count();

            // ── Recent Activity (Fix 3: load subject, format nicely) ─────────

            $recentActivity = \Spatie\Activitylog\Models\Activity::with(['causer', 'subject', 'subject.project'])
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($a) {
                    $subjectTitle = null;
                    $projectName  = null;

                    if ($a->subject) {
                        $subjectTitle = $a->subject->title ?? $a->subject->name ?? null;
                        if ($a->subject->relationLoaded('project')) {
                            $projectName = $a->subject->project?->name;
                        } elseif (isset($a->subject->project_id)) {
                            $projectName = \App\Models\Project::find($a->subject->project_id)?->name;
                        }
                    }

                    // Build a human-readable description
                    $action = $a->description;
                    if ($subjectTitle) {
                        $formatted = $action . ' — ' . $subjectTitle;
                        if ($projectName) {
                            $formatted .= ' (' . $projectName . ')';
                        }
                    } else {
                        $formatted = $action;
                    }

                    return [
                        'description'   => $formatted,
                        'causer_name'   => $a->causer?->name ?? 'System',
                        'subject_type'  => class_basename($a->subject_type ?? ''),
                        'subject_title' => $subjectTitle,
                        'project_name'  => $projectName,
                        'created_at'    => $a->created_at,
                        'time_ago'      => $a->created_at->diffForHumans(),
                    ];
                })
                ->filter(fn($a) => $a['description'] !== null)
                ->values()
                ->take(15)
                ->toArray();

            return compact(
                'revenueThisMonth', 'revenueLastMonth', 'revenueGrowth',
                'outstandingAmount', 'outstandingInvoicesCount', 'overdueInvoicesAmount', 'overdueInvoicesCount',
                'expensesThisMonth', 'expensesLastMonth', 'netThisMonth',
                'revenueByMonth', 'expensesByMonth', 'revenueByCustomer',
                'projectCounts', 'projectsByHealth', 'activeProjects', 'sprintDeadlines',
                'tasksByStatus', 'tasksPipeline', 'tasksOpenCount', 'tasksByPriority', 'tasksByType',
                'tasksCompletedFilled', 'overdueTasksCount', 'overdueTasksList',
                'unclaimedTasksCount', 'highPriorityOpenCount',
                'teamPerformance', 'topPerformers', 'teamWorkload',
                'customerCounts', 'activeCustomersCount',
                'collectionRate', 'totalInvoicedThisYear', 'totalCollectedThisYear',
                'availableCreditsSum', 'customersWithCredit',
                'bankAccounts', 'totalPendingAdvances', 'totalActiveLoansBalance',
                'lastPayrollRun', 'pendingPayrollRuns',
                'recentActivity'
            );
        });

        return view('dashboard.index', array_merge($data, compact('month', 'year', 'periodLabel')));
    }
}
