<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidTaskTransition;
use App\Models\Agent;
use App\Models\AgentTaskQueue;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskChangeRequest;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskClaimedNotification;
use App\Services\ChecklistTemplateService;
use App\Services\TaskStatusService;
use App\Support\Broadcasts\IssueBroadcastPayload;
use App\WebSocket\WebSocketBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use IssueBroadcastPayload;

    public function index(Request $request)
    {
        abort(404);
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tasks.create'), 403);

        $data = $this->validated($request);
        $task = Task::create($data);
        app(ChecklistTemplateService::class)->applyToTask($task);
        $this->syncAgentQueue($task, $data['agent_id'] ?? null, null);

        $workspaceId = $this->resolveWorkspaceId($task);

        if ($workspaceId) {
            WebSocketBroadcaster::broadcastIssueCreated(
                $task, $workspaceId, (string) auth()->id()
            );
        }

        if (! empty($data['assigned_to'])) {
            $assignee = User::find($data['assigned_to']);
            if ($assignee) {
                $assignee->notify(new TaskAssignedNotification($task->load('project'), auth()->user()));
            }
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Task created.');
    }

    public function show(Task $task)
    {
        $user = auth()->user();

        abort_unless($user->hasPermission('tasks.view'), 403);

        $relations = [
            'project',
            'assignee',
            'agent.runtime',
            'latestQueue',
            'statusHistory.actorUser',
            'statusHistory.actorAgent',
            'changeRequests.statusHistory',
            'changeRequests.media',
            'checklists',
            'media',
            'comments' => fn ($q) => $q->with(['author', 'media'])->latest(),
        ];

        if ($user->hasPermission('invoices.view')) {
            $relations[] = 'activeInvoiceItem.invoice';
            $relations[] = 'invoiceOverride.markedBy';
        }

        $task->load($relations);

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        $projects = Project::orderBy('name')->get(['id', 'name', 'components']);
        $users = User::orderBy('name')->get(['id', 'name']);
        $columns = KanbanColumn::orderBy('position')->get(['slug', 'name']);
        $componentOptions = $projects
            ->flatMap(fn (Project $project) => $project->components ?? [])
            ->unique()
            ->sort()
            ->values();
        $agents = Agent::query()
            ->where('owner_id', auth()->id())
            ->whereNull('archived_at')
            ->with('runtime:id,name,provider')
            ->orderBy('name')
            ->get();

        return view('tasks.form', compact('task', 'projects', 'users', 'columns', 'agents', 'componentOptions'));
    }

    public function update(Request $request, Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        $oldAssignedTo = $task->assigned_to;
        $oldAgentId = $task->agent_id;
        $oldStatus = $task->status;
        $data = $this->validated($request);

        // Status changes go through the central workflow service (legality,
        // conditions, validators, post-functions); everything else updates directly.
        $statusChanged = false;
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            try {
                app(TaskStatusService::class)->transition($task, $data['status']);
                $statusChanged = true;
            } catch (InvalidTaskTransition $e) {
                return back()->withInput()->withErrors(['status' => $e->getMessage()]);
            }
        }
        unset($data['status']);

        $task->update($data);

        $workspaceId = $this->resolveWorkspaceId($task);

        if ($workspaceId) {
            WebSocketBroadcaster::broadcastIssueUpdated(
                $task, $workspaceId, (string) auth()->id()
            );
        }

        if (array_key_exists('agent_id', $data)) {
            $task->refresh();
            $queueEntry = $this->syncAgentQueue($task, $data['agent_id'] ?? null, $oldAgentId);

            if ($queueEntry) {
                WebSocketBroadcaster::wakeupDaemon($queueEntry->runtime_id, $queueEntry->id);
            }
        }

        // Notify new assignee
        if (! empty($data['assigned_to']) && $data['assigned_to'] != $oldAssignedTo) {
            $assignee = User::find($data['assigned_to']);
            if ($assignee) {
                $assignee->notify(new TaskAssignedNotification($task->load('project'), auth()->user()));
            }
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.delete'), 403);
        $workspaceId = $this->resolveWorkspaceId($task);
        $taskId = (string) $task->id;
        $task->delete();

        if ($workspaceId) {
            WebSocketBroadcaster::broadcastIssueDeleted(
                $taskId, $workspaceId, (string) auth()->id()
            );
        }

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    public function advance(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        // "Advance" moves the task one step forward through the legal
        // transition map; a completed task can be reopened by managers.
        $next = match ($task->status) {
            'open', 'todo' => 'in-progress',
            'in-progress' => 'in-review',
            'in-review' => 'done',
            'done' => 'in-progress',
            default => null, // changes-requested and unknown statuses have no generic "advance"
        };

        if ($next === null) {
            return back()->with('error', 'This task cannot be advanced from its current status.');
        }

        try {
            app(TaskStatusService::class)->transition($task, $next);
        } catch (InvalidTaskTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        $workspaceId = $this->resolveWorkspaceId($task);

        if ($workspaceId) {
            WebSocketBroadcaster::broadcastIssueUpdated(
                $task, $workspaceId, (string) auth()->id()
            );
        }

        return back()->with('success', 'Task status updated.');
    }

    public function storeChangeRequest(Request $request, Task $task)
    {
        abort_unless(auth()->user()->hasPermission('tasks.edit_any'), 403);

        $data = $request->validate([
            'category' => ['required', 'in:Incomplete,Doesn\'t match spec,Bug / broken,Unprofessional / careless,Other'],
            'explanation' => ['required', 'string', 'min:3'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar'],
        ]);

        try {
            app(TaskStatusService::class)->transition(
                $task,
                'changes-requested',
                input: $data,
                postPersist: function (Task $task, ?TaskStatusHistory $history) use ($data, $request): void {
                    $changeRequest = TaskChangeRequest::query()->create([
                        'task_id' => $task->id,
                        'task_status_history_id' => $history?->id,
                        'category' => $data['category'],
                        'explanation' => $data['explanation'],
                    ]);

                    foreach ($request->file('attachments', []) as $file) {
                        $changeRequest->addMedia($file)
                            ->usingName($file->getClientOriginalName())
                            ->toMediaCollection('attachments');
                    }
                },
            );
        } catch (InvalidTaskTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Changes requested.');
    }

    public function claim(Task $task)
    {
        $user = auth()->user();
        Gate::authorize('claim', $task);

        if ($task->assigned_to !== null) {
            return back()->with('error', 'This task is already assigned to someone.');
        }

        $task->assigned_to = $user->id;
        $task->save();
        app(TaskStatusService::class)->transition($task, 'todo');

        $workspaceId = $this->resolveWorkspaceId($task);

        if ($workspaceId) {
            WebSocketBroadcaster::broadcastIssueUpdated(
                $task, $workspaceId, (string) $user->id
            );
        }

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->log('claimed task');

        $managers = User::whereHas('roleModel', fn ($q) => $q->whereIn('slug', ['super-admin', 'manager']))->get();
        foreach ($managers as $manager) {
            $manager->notify(new TaskClaimedNotification($task->load('project'), $user));
        }

        SlackNotificationHelper::notifyOnce(new TaskClaimedNotification($task->load('project'), $user));

        return back()->with('success', 'You are now assigned to this task.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'agent_id' => 'nullable|exists:agents,id',
            'type' => 'required|in:bug,feature,change',
            'component' => 'nullable|string|max:100',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|string',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'labels' => 'nullable|array',
            'labels.*' => 'string|max:50',
            'labels_csv' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'source' => 'required|in:manual,ai-chat',
            'original_input' => 'nullable|string',
            'guide' => 'nullable|string',
        ]);

        // The classic HTML form submits labels as a comma-separated string
        if (array_key_exists('labels_csv', $data)) {
            $data['labels'] = collect(explode(',', (string) $data['labels_csv']))
                ->map(fn (string $label) => trim($label))
                ->filter(fn (string $label) => $label !== '')
                ->unique(fn (string $label) => mb_strtolower($label))
                ->values()
                ->all();
            unset($data['labels_csv']);
        }

        return $data;
    }

    private function syncAgentQueue(Task $task, ?string $newAgentId, ?string $oldAgentId): ?AgentTaskQueue
    {
        if ((string) ($newAgentId ?? '') === (string) ($oldAgentId ?? '')) {
            return null;
        }

        AgentTaskQueue::query()
            ->where('task_id', $task->id)
            ->whereIn('status', ['queued', 'dispatched'])
            ->update(['status' => 'cancelled']);

        if (! $newAgentId) {
            return null;
        }

        $agent = Agent::query()
            ->where('id', $newAgentId)
            ->whereNull('archived_at')
            ->first();

        if (! $agent) {
            return null;
        }

        $task->loadMissing('checklists');

        return AgentTaskQueue::create([
            'task_id' => $task->id,
            'agent_id' => $agent->id,
            'runtime_id' => $agent->runtime_id,
            'team_id' => $agent->team_id,
            'status' => 'queued',
            'prompt' => AgentTaskQueue::buildPrompt($task),
        ]);
    }
}
