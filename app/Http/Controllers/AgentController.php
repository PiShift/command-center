<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\AgentTaskQueue;
use App\Models\Task;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->toString() ?: 'mine';
        if (! in_array($tab, ['mine', 'all', 'archived'], true)) {
            $tab = 'mine';
        }

        $user = $request->user();
        $userTeamIds = $user->teams()->pluck('teams.id');
        $canViewAll = in_array($user->roleModel?->slug, ['super-admin', 'manager'], true);

        $query = Agent::with(['runtime', 'owner']);

        if ($tab === 'mine') {
            $query->where('owner_id', $user->id)->whereNull('archived_at');
        } elseif ($tab === 'archived') {
            $query->whereNotNull('archived_at');

            if (! $canViewAll) {
                $query->where(function ($agentQuery) use ($user, $userTeamIds): void {
                    $agentQuery->where('owner_id', $user->id)
                        ->orWhere(function ($workspaceQuery) use ($userTeamIds): void {
                            $workspaceQuery->where('visibility', 'workspace')
                                ->whereIn('team_id', $userTeamIds);
                        });
                });
            }
        } else {
            $query->whereNull('archived_at');

            if (! $canViewAll) {
                $query->where(function ($agentQuery) use ($user, $userTeamIds): void {
                    $agentQuery->where('owner_id', $user->id)
                        ->orWhere(function ($workspaceQuery) use ($userTeamIds): void {
                            $workspaceQuery->where('visibility', 'workspace')
                                ->whereIn('team_id', $userTeamIds);
                        });
                });
            }
        }

        $agents = $query->orderBy('name')->get();
        $agentIds = $agents->pluck('id');

        $runCounts = AgentTaskQueue::query()
            ->whereIn('agent_id', $agentIds)
            ->whereIn('status', ['completed', 'failed'])
            ->selectRaw('agent_id, count(*) as total, max(completed_at) as last_active')
            ->groupBy('agent_id')
            ->get()
            ->keyBy('agent_id');

        $runtimes = AgentRuntime::query()
            ->where('user_id', $user->id)
            ->where('status', 'online')
            ->orderBy('name')
            ->get();

        return view('agents.index', compact('agents', 'tab', 'runCounts', 'runtimes'));
    }

    public function show(Request $request, Agent $agent)
    {
        $this->authorizeView($request->user(), $agent);

        $agent->load(['runtime', 'owner']);

        $driver = DB::connection()->getDriverName();
        $avgSecondsExpr = $driver === 'mysql'
            ? 'avg(timestampdiff(second, started_at, completed_at))'
            : 'avg(extract(epoch from completed_at - started_at))';

        $stats = AgentTaskQueue::query()
            ->where('agent_id', $agent->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['completed', 'failed'])
            ->selectRaw(
                "count(*) as total_runs,
                sum(case when status = 'completed' then 1 else 0 end) as completed,
                sum(case when status = 'failed' then 1 else 0 end) as failed,
                {$avgSecondsExpr} as avg_seconds"
            )
            ->first();

        $recentWork = AgentTaskQueue::query()
            ->where('agent_id', $agent->id)
            ->whereIn('status', ['completed', 'failed'])
            ->with('task:id,title,project_id')
            ->orderByDesc('completed_at')
            ->limit(10)
            ->get();

        $activeTask = AgentTaskQueue::query()
            ->where('agent_id', $agent->id)
            ->whereIn('status', ['queued', 'dispatched', 'running'])
            ->with('task:id,title,project_id')
            ->latest()
            ->first();

        $tasks = Task::query()
            ->where('agent_id', $agent->id)
            ->with(['project:id,name,color'])
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('status');

        $runtimes = AgentRuntime::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'online')
            ->orderBy('name')
            ->get();

        $assignedSkills = $agent->skills()->get();
        $availableSkills = Skill::where('team_id', $agent->team_id)
            ->whereNotIn('id', $assignedSkills->pluck('id'))
            ->orderBy('name')
            ->get();

        $skills = collect(data_get($agent->custom_args, 'skills', []))
            ->map(fn ($skill) => trim((string) $skill))
            ->filter()
            ->values();

        return view('agents.show', compact('agent', 'stats', 'recentWork', 'activeTask', 'tasks', 'runtimes', 'skills', 'assignedSkills', 'availableSkills'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:500'],
            'instructions'         => ['nullable', 'string'],
            'runtime_id'           => ['required', 'uuid'],
            'visibility'           => ['required', 'in:workspace,private'],
            'max_concurrent_tasks' => ['nullable', 'integer', 'min:1', 'max:50'],
            'model'                => ['nullable', 'string', 'max:255'],
            'skills'               => ['nullable', 'string'],
        ]);

        $runtime = AgentRuntime::query()
            ->whereKey($data['runtime_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'online')
            ->firstOrFail();

        $customArgs = $this->mergeSkillsIntoCustomArgs(null, $data['skills'] ?? null);

        $agent = Agent::create([
            'team_id'              => $runtime->team_id,
            'runtime_id'           => $runtime->id,
            'owner_id'             => $request->user()->id,
            'name'                 => $data['name'],
            'description'          => $data['description'] ?? null,
            'instructions'         => $data['instructions'] ?? null,
            'visibility'           => $data['visibility'],
            'status'               => 'idle',
            'max_concurrent_tasks' => (int) ($data['max_concurrent_tasks'] ?? 6),
            'model'                => $data['model'] ?? null,
            'custom_args'          => $customArgs,
        ]);

        return redirect()->route('agents.show', $agent)->with('success', 'Agent created.');
    }

    public function update(Request $request, Agent $agent)
    {
        $this->authorizeView($request->user(), $agent);

        $data = $request->validate([
            'name'                 => ['sometimes', 'required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:500'],
            'instructions'         => ['nullable', 'string'],
            'runtime_id'           => ['sometimes', 'uuid'],
            'visibility'           => ['sometimes', 'in:workspace,private'],
            'max_concurrent_tasks' => ['nullable', 'integer', 'min:1', 'max:50'],
            'model'                => ['nullable', 'string', 'max:255'],
            'skills'               => ['nullable', 'string'],
        ]);

        $updates = [];

        foreach (['name', 'description', 'instructions', 'visibility', 'model', 'max_concurrent_tasks'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if (array_key_exists('max_concurrent_tasks', $updates)) {
            $updates['max_concurrent_tasks'] = (int) $updates['max_concurrent_tasks'];
        }

        if (array_key_exists('runtime_id', $data)) {
            $runtime = AgentRuntime::query()
                ->whereKey($data['runtime_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $updates['runtime_id'] = $runtime->id;
            $updates['team_id'] = $runtime->team_id;
        }

        if (array_key_exists('skills', $data)) {
            $updates['custom_args'] = $this->mergeSkillsIntoCustomArgs($agent->custom_args, $data['skills']);
        }

        $agent->update($updates);

        return back()->with('success', 'Agent updated.');
    }

    public function archive(Request $request, Agent $agent)
    {
        $this->authorizeView($request->user(), $agent);

        $agent->update([
            'archived_at' => now(),
            'status'      => 'offline',
        ]);

        return redirect()->route('agents.index')->with('success', 'Agent archived.');
    }

    public function restore(Request $request, Agent $agent)
    {
        $this->authorizeView($request->user(), $agent);

        $agent->update(['archived_at' => null]);

        return back()->with('success', 'Agent restored.');
    }

    public function destroy(Request $request, Agent $agent)
    {
        $this->authorizeView($request->user(), $agent);

        $agent->delete();

        return redirect()->route('agents.index')->with('success', 'Agent deleted.');
    }

    private function canSeeAllAgents($user): bool
    {
        return in_array($user->roleModel?->slug, ['super-admin', 'manager'], true)
            || $user->hasPermission('settings.manage');
    }

    private function authorizeView($user, Agent $agent): void
    {
        if ($this->canSeeAllAgents($user)) {
            return;
        }

        if ((int) $agent->owner_id === (int) $user->id) {
            return;
        }

        if ($agent->visibility === 'workspace' && $user->teams()->where('teams.id', $agent->team_id)->exists()) {
            return;
        }

        abort(403);
    }

    private function mergeSkillsIntoCustomArgs(?array $customArgs, ?string $skillsInput): ?array
    {
        if ($skillsInput === null) {
            return $customArgs;
        }

        $skills = $this->parseSkills($skillsInput);
        $customArgs = $customArgs ?? [];

        if ($skills === []) {
            unset($customArgs['skills']);
        } else {
            $customArgs['skills'] = $skills;
        }

        return $customArgs === [] ? null : $customArgs;
    }

    private function parseSkills(string $skillsInput): array
    {
        return collect(preg_split('/[\r\n,]+/', $skillsInput) ?: [])
            ->map(fn ($skill) => trim((string) $skill))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}