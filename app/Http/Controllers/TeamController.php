<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamLeadAssignedNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    private const RETURN_CATEGORIES = [
        'Incomplete',
        "Doesn't match spec",
        'Bug / broken',
        'Unprofessional / careless',
        'Other',
    ];

    private const SORT_COLUMNS = [
        'name',
        'tasks_completed',
        'first_time_right_pct',
        'total_returns',
        'avg_rounds_per_returned_task',
        'avg_time_in_progress_seconds',
        'avg_return_resolution_seconds',
        'blocked_transition_attempts',
    ];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('teams.view'), 403);

        $activeTab = (string) $request->query('tab', 'overview');

        if ($activeTab === 'accountability') {
            abort_unless($this->canViewAccountability(), 403);

            [$start, $end, $preset] = $this->resolveRange($request);

            if ($preset !== 'custom' && ($request->filled('start_date') || $request->filled('end_date'))) {
                $query = $request->query();
                unset($query['start_date'], $query['end_date']);
                $query['tab'] = 'accountability';
                $query['range'] = $preset;

                return redirect()->route('teams.index', $query);
            }

            $developers = User::query()
                ->select(['id', 'name'])
                ->whereHas('roleModel', fn ($query) => $query->where('slug', 'developer'))
                ->orderBy('name')
                ->get();

            $developerIds = $developers->pluck('id');

            $completedTransitions = TaskStatusHistory::query()
                ->select(['task_status_histories.task_id', 'task_status_histories.created_at as completed_at', 'tasks.assigned_to as developer_id'])
                ->join('tasks', 'tasks.id', '=', 'task_status_histories.task_id')
                ->where('task_status_histories.to_status', 'done')
                ->whereIn('tasks.assigned_to', $developerIds)
                ->whereBetween('task_status_histories.created_at', [$start, $end])
                ->orderBy('task_status_histories.created_at')
                ->get();

            $completedByDeveloper = $completedTransitions->groupBy('developer_id');

            $returnsInRange = DB::table('task_change_requests')
                ->join('tasks', 'tasks.id', '=', 'task_change_requests.task_id')
                ->whereIn('tasks.assigned_to', $developerIds)
                ->whereBetween('task_change_requests.created_at', [$start, $end])
                ->select([
                    'tasks.assigned_to as developer_id',
                    'task_change_requests.task_id',
                    'task_change_requests.category',
                    'task_change_requests.created_at',
                ])
                ->orderBy('task_change_requests.created_at')
                ->get();

            $tasksNeedingHistory = $completedTransitions
                ->pluck('task_id')
                ->merge($returnsInRange->pluck('task_id'))
                ->unique()
                ->values();

            $taskHistories = TaskStatusHistory::query()
                ->whereIn('task_id', $tasksNeedingHistory)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->groupBy('task_id');

            $returnsByDeveloper = $returnsInRange->groupBy('developer_id');

            $activityTable = (string) config('activitylog.table_name', 'activity_log');

            $blockedAttemptsByDeveloper = DB::table($activityTable)
                ->join('tasks', function ($join) use ($activityTable): void {
                    $join->on('tasks.id', '=', $activityTable.'.subject_id')
                        ->where($activityTable.'.subject_type', Task::class);
                })
                ->where($activityTable.'.log_name', 'task-workflow')
                ->where($activityTable.'.description', 'transition_blocked')
                ->whereBetween($activityTable.'.created_at', [$start, $end])
                ->whereIn('tasks.assigned_to', $developerIds)
                ->where(function ($query) use ($activityTable): void {
                    $query->where($activityTable.'.properties->stage', 'validator')
                        ->where($activityTable.'.properties->to_status', 'in-review');
                })
                ->selectRaw('tasks.assigned_to as developer_id, count(*) as blocked_count')
                ->groupBy('tasks.assigned_to')
                ->pluck('blocked_count', 'developer_id');

            $rows = $developers->map(function (User $developer) use ($completedByDeveloper, $taskHistories, $returnsByDeveloper, $blockedAttemptsByDeveloper): array {
                $completedRows = collect($completedByDeveloper->get($developer->id, collect()))
                    ->groupBy('task_id')
                    ->map(fn (Collection $rows) => $rows->last());

                $completedTaskCount = $completedRows->count();
                $firstTimeRightCount = 0;
                $inProgressDurations = [];

                foreach ($completedRows as $taskId => $completedRow) {
                    $history = $taskHistories->get((int) $taskId, collect());
                    $completedAt = Carbon::parse($completedRow->completed_at);

                    $returnsBeforeDone = $history
                        ->filter(fn (TaskStatusHistory $item) => $item->to_status === 'changes-requested' && $item->created_at <= $completedAt)
                        ->count();

                    if ($returnsBeforeDone === 0) {
                        $firstTimeRightCount++;
                    }

                    $inProgressAt = $history->first(fn (TaskStatusHistory $item) => $item->to_status === 'in-progress');
                    $reviewAt = $history->first(fn (TaskStatusHistory $item) => $item->to_status === 'in-review'
                        && $inProgressAt
                        && $item->created_at >= $inProgressAt->created_at);

                    if ($inProgressAt && $reviewAt) {
                        $inProgressDurations[] = Carbon::parse($reviewAt->created_at)->diffInSeconds(Carbon::parse($inProgressAt->created_at));
                    }
                }

                $developerReturns = collect($returnsByDeveloper->get($developer->id, collect()));

                $returnsByTask = $developerReturns
                    ->groupBy('task_id')
                    ->map(fn (Collection $items) => $items->count());

                $avgRounds = $returnsByTask->isEmpty() ? null : round($returnsByTask->avg(), 2);

                $returnResolutionDurations = [];
                $reasonBreakdown = collect(self::RETURN_CATEGORIES)->mapWithKeys(fn (string $category) => [$category => 0])->all();

                foreach ($developerReturns as $return) {
                    if (isset($reasonBreakdown[$return->category])) {
                        $reasonBreakdown[$return->category]++;
                    }

                    $history = $taskHistories->get((int) $return->task_id, collect());
                    $returnAt = Carbon::parse($return->created_at);
                    $nextReview = $history->first(fn (TaskStatusHistory $item) => $item->to_status === 'in-review' && $item->created_at > $returnAt);

                    if ($nextReview) {
                        $returnResolutionDurations[] = Carbon::parse($nextReview->created_at)->diffInSeconds($returnAt);
                    }
                }

                $firstTimeRightPct = $completedTaskCount === 0
                    ? null
                    : round(($firstTimeRightCount / $completedTaskCount) * 100, 1);

                return [
                    'developer_id' => $developer->id,
                    'name' => $developer->name,
                    'tasks_completed' => $completedTaskCount,
                    'first_time_right_pct' => $firstTimeRightPct,
                    'total_returns' => $developerReturns->count(),
                    'return_breakdown' => $reasonBreakdown,
                    'avg_rounds_per_returned_task' => $avgRounds,
                    'avg_time_in_progress_seconds' => empty($inProgressDurations) ? null : (int) round(collect($inProgressDurations)->avg()),
                    'avg_return_resolution_seconds' => empty($returnResolutionDurations) ? null : (int) round(collect($returnResolutionDurations)->avg()),
                    'blocked_transition_attempts' => (int) ($blockedAttemptsByDeveloper[$developer->id] ?? 0),
                ];
            });

            [$sort, $dir] = $this->resolveSort($request);

            $rows = $rows->sort(function (array $a, array $b) use ($sort, $dir): int {
                $av = $a[$sort] ?? null;
                $bv = $b[$sort] ?? null;

                $value = is_string($av) && is_string($bv)
                    ? strcasecmp($av, $bv)
                    : (($av <=> $bv) ?: strcasecmp($a['name'], $b['name']));

                return $dir === 'asc' ? $value : -$value;
            })->values();

            $selectedDeveloperId = $request->integer('developer_id') ?: null;
            $selectedDeveloper = $selectedDeveloperId ? $developers->firstWhere('id', $selectedDeveloperId) : null;

            $drilldownTasks = collect();

            if ($selectedDeveloper) {
                $drilldownTasks = $this->buildDeveloperDrilldown($selectedDeveloper->id, $start, $end);
            }

            return view('teams.index', [
                'activeTab' => 'accountability',
                'rows' => $rows,
                'rangeStart' => $start,
                'rangeEnd' => $end,
                'rangePreset' => $preset,
                'sort' => $sort,
                'dir' => $dir,
                'selectedDeveloper' => $selectedDeveloper,
                'drilldownTasks' => $drilldownTasks,
            ]);
        }

        $teams = Team::with(['lead', 'members'])
                     ->withCount('members')
                     ->orderBy('name')
                     ->get();

        return view('teams.index', [
            'teams' => $teams,
            'activeTab' => 'overview',
        ]);
    }

    private function canViewAccountability(): bool
    {
        $role = auth()->user()?->roleModel?->slug;

        return in_array($role, ['manager', 'super-admin'], true);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string}
     */
    private function resolveRange(Request $request): array
    {
        $preset = (string) $request->input('range', 'this_month');

        if ($preset === 'last_30_days') {
            return [now()->subDays(29)->startOfDay(), now()->endOfDay(), $preset];
        }

        if ($preset === 'last_3_months') {
            return [now()->subMonthsNoOverflow(3)->startOfDay(), now()->endOfDay(), $preset];
        }

        if ($preset === 'custom') {
            $validated = $request->validate([
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);

            return [
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay(),
                $preset,
            ];
        }

        return [now()->startOfMonth(), now()->endOfDay(), 'this_month'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveSort(Request $request): array
    {
        $sort = (string) $request->input('sort', 'tasks_completed');
        $dir = strtolower((string) $request->input('dir', 'desc'));

        if (! in_array($sort, self::SORT_COLUMNS, true)) {
            $sort = 'tasks_completed';
        }

        if (! in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'desc';
        }

        return [$sort, $dir];
    }

    private function buildDeveloperDrilldown(int $developerId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $taskIds = DB::table('tasks')
            ->where('assigned_to', $developerId)
            ->where(function ($query) use ($start, $end): void {
                $query->whereExists(function ($sub) use ($start, $end): void {
                    $sub->selectRaw('1')
                        ->from('task_status_histories')
                        ->whereColumn('task_status_histories.task_id', 'tasks.id')
                        ->where('task_status_histories.to_status', 'done')
                        ->whereBetween('task_status_histories.created_at', [$start, $end]);
                })->orWhereExists(function ($sub) use ($start, $end): void {
                    $sub->selectRaw('1')
                        ->from('task_change_requests')
                        ->whereColumn('task_change_requests.task_id', 'tasks.id')
                        ->whereBetween('task_change_requests.created_at', [$start, $end]);
                });
            })
            ->pluck('id');

        if ($taskIds->isEmpty()) {
            return collect();
        }

        $tasks = Task::query()
            ->with(['project:id,name'])
            ->whereIn('id', $taskIds)
            ->orderByDesc('id')
            ->get();

        $doneInRange = TaskStatusHistory::query()
            ->whereIn('task_id', $taskIds)
            ->where('to_status', 'done')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get()
            ->groupBy('task_id')
            ->map(fn (Collection $items) => optional($items->last())->created_at);

        $returnsInRange = DB::table('task_change_requests')
            ->whereIn('task_id', $taskIds)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get()
            ->groupBy('task_id');

        return $tasks->map(function (Task $task) use ($doneInRange, $returnsInRange): array {
            $returns = collect($returnsInRange->get($task->id, collect()));

            return [
                'id' => $task->id,
                'title' => $task->title,
                'project' => $task->project?->name,
                'completed_at' => $doneInRange->get($task->id),
                'status' => $task->status,
                'return_rounds' => $returns->count(),
                'return_categories' => $returns->pluck('category')->values()->all(),
            ];
        })->sortByDesc(fn (array $row) => $row['completed_at'] ?? '')->values();
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $users = User::orderBy('name')->get(['id', 'name', 'initials', 'color']);
        return view('teams.form', ['team' => null, 'users' => $users, 'members' => collect()]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $data = $request->validate([
            'name'         => 'required|string|max:255|unique:teams,name',
            'description'  => 'nullable|string|max:1000',
            'lead_user_id' => 'nullable|exists:users,id',
            'members'      => 'nullable|array',
            'members.*'    => 'exists:users,id',
        ]);

        $team = Team::create([
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'lead_user_id' => $data['lead_user_id'] ?? null,
        ]);

        if (!empty($data['members'])) {
            $team->members()->sync($data['members']);
        }

        return redirect()->route('teams.show', $team)->with('success', 'Team created.');
    }

    public function show(Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.view'), 403);

        $team->load(['lead', 'members', 'projects']);

        $memberIds = $team->members->pluck('id');

        $availableUsers = User::orderBy('name')
            ->get(['id', 'name', 'initials', 'color'])
            ->filter(fn ($u) => !$memberIds->contains($u->id))
            ->values();

        $teamProjectIds = $team->projects->pluck('id');
        $availableProjects = \App\Models\Project::orderBy('name')
            ->get(['id', 'name', 'status', 'color'])
            ->filter(fn ($p) => !$teamProjectIds->contains($p->id))
            ->values();

        return view('teams.show', compact('team', 'availableUsers', 'availableProjects'));
    }

    public function edit(Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $team->load('members');
        $users = User::orderBy('name')->get(['id', 'name', 'initials', 'color']);
        $members = $team->members;

        return view('teams.form', compact('team', 'users', 'members'));
    }

    public function update(Request $request, Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $data = $request->validate([
            'name'         => 'required|string|max:255|unique:teams,name,' . $team->id,
            'description'  => 'nullable|string|max:1000',
            'lead_user_id' => 'nullable|exists:users,id',
            'members'      => 'nullable|array',
            'members.*'    => 'exists:users,id',
        ]);

        $oldLeadId = $team->lead_user_id;

        $team->update([
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'lead_user_id' => $data['lead_user_id'] ?? null,
        ]);

        $team->members()->sync($data['members'] ?? []);

        // Notify new lead if changed
        if (! empty($data['lead_user_id']) && $data['lead_user_id'] != $oldLeadId) {
            $newLead = User::find($data['lead_user_id']);
            if ($newLead) {
                $newLead->notify(new TeamLeadAssignedNotification($team, $newLead));
            }
        }

        return redirect()->route('teams.show', $team)->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        // Detach from projects (pivot only — does NOT remove users from projects)
        $team->projects()->detach();
        $team->members()->detach();
        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }
}
