<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()?->roleModel?->slug === 'developer') {
            return redirect()->route('board');
        }

        $data = Cache::tags(['dashboard'])->remember('dashboard.manager', 300, function () {
            $now            = now();
            $thisMonthStart = $now->copy()->startOfMonth();
            $thisMonthEnd   = $now->copy()->endOfMonth();
            $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
            $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();

            // ── Financial ────────────────────────────────────────────────────

            $revenueThisMonth = InvoicePayment::whereBetween('payment_date', [$thisMonthStart, $thisMonthEnd])
                ->sum('amount');

            $revenueLastMonth = InvoicePayment::whereBetween('payment_date', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            $revenueGrowth = $revenueLastMonth > 0
                ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                : ($revenueThisMonth > 0 ? 100 : 0);

            $outstandingAmount = Invoice::where('status', 'published')
                ->whereNotIn('payment_status', ['paid'])
                ->sum(DB::raw('total - amount_paid'));

            $overdueInvoices = Invoice::where('status', 'published')
                ->whereNotIn('payment_status', ['paid'])
                ->whereDate('due_date', '<', $now->toDateString())
                ->get(['id', 'total', 'amount_paid']);

            $overdueInvoicesAmount = $overdueInvoices->sum(fn($i) => $i->total - $i->amount_paid);
            $overdueInvoicesCount  = $overdueInvoices->count();

            $expensesThisMonth = Expense::confirmed()
                ->whereBetween('expense_date', [$thisMonthStart, $thisMonthEnd])
                ->sum('amount');

            $expensesLastMonth = Expense::confirmed()
                ->whereBetween('expense_date', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            $netThisMonth = $revenueThisMonth - $expensesThisMonth;

            // Revenue by month — last 6 months
            $revenueByMonth  = [];
            $expensesByMonth = [];
            for ($i = 5; $i >= 0; $i--) {
                $m     = $now->copy()->subMonths($i);
                $label = $m->format('M Y');
                $revenueByMonth[$label] = InvoicePayment::whereBetween('payment_date', [
                    $m->copy()->startOfMonth(), $m->copy()->endOfMonth(),
                ])->sum('amount');
                $expensesByMonth[$label] = Expense::confirmed()->whereBetween('expense_date', [
                    $m->copy()->startOfMonth(), $m->copy()->endOfMonth(),
                ])->sum('amount');
            }

            // Revenue by customer — top 5 this year
            $revenueByCustomer = InvoicePayment::join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
                ->join('customers', 'invoices.customer_id', '=', 'customers.id')
                ->whereYear('invoice_payments.payment_date', $now->year)
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

            // Active projects with task counts and active sprint
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
                $activeSprint    = $p->sprints->first();
                $sprintTotalTask = $activeSprint ? $activeSprint->tasks->count() : 0;
                $sprintDoneTask  = $activeSprint ? $activeSprint->tasks->where('status', 'done')->count() : 0;
                $progressPct     = $p->total_tasks_count > 0
                    ? round(($p->done_tasks_count / $p->total_tasks_count) * 100)
                    : 0;

                return [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'health'          => $p->health,
                    'status'          => $p->status,
                    'customer_name'   => $p->customer?->name,
                    'sprint_name'     => $activeSprint?->name,
                    'sprint_deadline' => $activeSprint?->deadline,
                    'sprint_days_left'=> $activeSprint?->deadline ? now()->diffInDays($activeSprint->deadline, false) : null,
                    'open'            => $p->open_tasks_count,
                    'in_progress'     => $p->progress_tasks_count,
                    'done'            => $p->done_tasks_count,
                    'total'           => $p->total_tasks_count,
                    'progress_pct'    => $progressPct,
                ];
            })->toArray();

            // Sprint deadlines — next 14 days
            $sprintDeadlines = Sprint::where('status', 'active')
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [$now->toDateString(), $now->copy()->addDays(14)->toDateString()])
                ->with(['project', 'tasks'])
                ->orderBy('deadline')
                ->get()
                ->map(function ($s) use ($now) {
                    $total    = $s->tasks->count();
                    $done     = $s->tasks->where('status', 'done')->count();
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

            // Tasks completed per day — last 30 days
            $tasksCompletedByDay = Task::where('status', 'done')
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $now->copy()->subDays(29)->startOfDay())
                ->select(DB::raw('DATE(completed_at) as day'), DB::raw('count(*) as count'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('count', 'day')
                ->toArray();

            // Fill missing days with 0
            $tasksCompletedFilled = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i)->toDateString();
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

            $unclaimedTasksCount  = Task::whereIn('status', ['open', 'todo'])->whereNull('assigned_to')->count();
            $highPriorityOpenCount = Task::whereNotIn('status', ['done'])->where('priority', 'high')->count();

            // ── Team / People ────────────────────────────────────────────────

            $developers = User::whereHas('roleModel', fn($q) => $q->whereIn('slug', ['developer', 'manager', 'super-admin']))
                ->get(['id', 'name', 'initials', 'color']);

            $thisMonthDone = Task::where('status', 'done')
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$thisMonthStart, $thisMonthEnd])
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

            // ── Customers ───────────────────────────────────────────────────

            $customerCounts = [
                'prospect' => Customer::where('status', 'prospect')->count(),
                'active'   => Customer::where('status', 'active')->count(),
                'churned'  => Customer::where('status', 'churned')->count(),
                'total'    => Customer::count(),
            ];

            $activeCustomersCount = Customer::whereHas('projects', fn($q) => $q->where('status', 'active'))->count();

            // ── Recent Activity ──────────────────────────────────────────────

            $recentActivity = \Spatie\Activitylog\Models\Activity::with('causer')
                ->latest()
                ->limit(15)
                ->get()
                ->map(fn($a) => [
                    'description'  => $a->description,
                    'causer_name'  => $a->causer?->name ?? 'System',
                    'subject_type' => class_basename($a->subject_type ?? ''),
                    'created_at'   => $a->created_at,
                    'time_ago'     => $a->created_at->diffForHumans(),
                ])
                ->toArray();

            return compact(
                'revenueThisMonth', 'revenueLastMonth', 'revenueGrowth',
                'outstandingAmount', 'overdueInvoicesAmount', 'overdueInvoicesCount',
                'expensesThisMonth', 'expensesLastMonth', 'netThisMonth',
                'revenueByMonth', 'expensesByMonth', 'revenueByCustomer',
                'projectCounts', 'projectsByHealth', 'activeProjects', 'sprintDeadlines',
                'tasksByStatus', 'tasksByPriority', 'tasksByType',
                'tasksCompletedFilled', 'overdueTasksCount', 'overdueTasksList',
                'unclaimedTasksCount', 'highPriorityOpenCount',
                'teamPerformance', 'topPerformers', 'teamWorkload',
                'customerCounts', 'activeCustomersCount',
                'recentActivity'
            );
        });

        return view('dashboard.index', $data);
    }
}
