<?php

namespace App\Services;

use App\Exceptions\InvalidTaskTransition;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\TaskStatusChangedNotification;
use App\Support\Workflow\TransitionActor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * The single gateway for every task status change.
 *
 * Every code path that moves a task between statuses MUST go through
 * transition(). The pipeline runs in strict order:
 *
 *   1. Legality   — the transition must exist in config/task-workflow.php
 *   2. Conditions — actor-level rules (permissions) for this transition
 *   3. Validators — task-data rules for this transition
 *   4. Persist    — the actual status write
 *   5. Post-functions — history, completed_at, notifications, side effects
 *
 * New rules are added as entries in config/task-workflow.php — never as
 * ad-hoc checks inside controllers or Livewire components.
 */
class TaskStatusService
{
    /**
     * Transition a task to a new status.
     *
     * @param  array<string, mixed>  $input  Extra payload for transitions that
     *                                       require input (e.g. category +
     *                                       explanation for changes-requested).
     * @param  callable|null  $postPersist  Extra side effects to run inside
     *                                      the same flow, right after the
     *                                      status is persisted (e.g. attach a
     *                                      change request to the history row).
     * @throws InvalidTaskTransition When any gate rejects the transition.
     */
    public function transition(
        Task $task,
        string $toStatus,
        ?TransitionActor $actor = null,
        array $input = [],
        ?callable $postPersist = null,
    ): ?TaskStatusHistory {
        $actor ??= TransitionActor::current();
        $fromStatus = (string) $task->status;

        // 1. Legality — explicit transition map, rejected outright otherwise
        if ($fromStatus !== $toStatus && ! $this->isLegal($fromStatus, $toStatus)) {
            $exception = InvalidTaskTransition::illegal($fromStatus, $toStatus);
            $this->recordBlockedTransition($task, $actor, $exception);

            throw $exception;
        }

        // 2. Conditions — is this actor allowed to attempt this transition?
        foreach ($this->rules('conditions', $fromStatus, $toStatus) as $condition) {
            if (! ($condition['when'])($actor, $task, $fromStatus, $toStatus)) {
                $exception = InvalidTaskTransition::conditionFailed(
                    $fromStatus,
                    $toStatus,
                    $condition['message'] ?? 'You are not allowed to perform this status change.',
                );
                $this->recordBlockedTransition($task, $actor, $exception);

                throw $exception;
            }
        }

        // 2b. Required input — transitions like changes-requested cannot run
        //     without their mandatory payload (reason, category, ...).
        $this->assertRequiredInput($task, $actor, $fromStatus, $toStatus, $input);

        // 3. Validators — is the task's data valid for this transition?
        foreach ($this->rules('validators', $fromStatus, $toStatus) as $validator) {
            $error = ($validator['validate'])($task, $fromStatus, $toStatus);

            if ($error !== null) {
                $exception = InvalidTaskTransition::validationFailed($fromStatus, $toStatus, $error);
                $this->recordBlockedTransition($task, $actor, $exception);

                throw $exception;
            }
        }

        // 4. Persist the status change. history_written marks the save so the
        //    Task model fallback hook skips it — history is a post-function here.
        $history = null;

        if ($fromStatus !== $toStatus) {
            $task->history_written = true;
            $task->status = $toStatus;
            $task->save();

            // Every status entry lands at the top of the destination column.
            $this->placeAtTopOfStatus($task, $toStatus);

            // 5. Post-functions (strict order)
            $history = $this->writeHistory($task, $fromStatus, $toStatus, $actor);
            $this->syncCompletedAt($task, $toStatus);
            $this->resetReworkChecklists($task, $fromStatus, $toStatus, $actor);

            if ($postPersist) {
                $postPersist($task, $history);
            }

            $this->notifyStatusChange($task, $fromStatus, $toStatus, $actor);
        }

        return $history;
    }

    private function placeAtTopOfStatus(Task $task, string $status): void
    {
        if (! Schema::hasColumn('tasks', 'kanban_position')) {
            return;
        }

        $currentMin = Task::query()
            ->where('status', $status)
            ->whereKeyNot($task->id)
            ->min('kanban_position');

        $task->kanban_position = $currentMin !== null ? ((int) $currentMin - 1) : 0;
        $task->saveQuietly();
    }

    /**
     * Preview whether a transition would pass all gates, without persisting.
     * Useful for rendering available actions in the UI.
     */
    public function canTransition(Task $task, string $toStatus, ?TransitionActor $actor = null): bool
    {
        try {
            $actor ??= TransitionActor::current();
            $fromStatus = (string) $task->status;

            if ($fromStatus === $toStatus || ! $this->isLegal($fromStatus, $toStatus)) {
                return false;
            }

            foreach ($this->rules('conditions', $fromStatus, $toStatus) as $condition) {
                if (! ($condition['when'])($actor, $task, $fromStatus, $toStatus)) {
                    return false;
                }
            }

            foreach ($this->rules('validators', $fromStatus, $toStatus) as $validator) {
                if (($validator['validate'])($task, $fromStatus, $toStatus) !== null) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function isLegal(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, config("task-workflow.transitions.{$fromStatus}", []), true);
    }

    /**
     * All configured rules of a kind that apply to a specific transition.
     *
     * @return list<array<string, mixed>>
     */
    private function rules(string $kind, string $fromStatus, string $toStatus): array
    {
        return collect(config("task-workflow.{$kind}", []))
            ->filter(function (array $rule) use ($fromStatus, $toStatus) {
                $fromMatches = ! isset($rule['from']) || $rule['from'] === $fromStatus;
                $toMatches = ! isset($rule['to']) || $rule['to'] === $toStatus;

                return $fromMatches && $toMatches;
            })
            ->values()
            ->all();
    }

    private function writeHistory(Task $task, string $fromStatus, string $toStatus, TransitionActor $actor): ?TaskStatusHistory
    {
        // Some integration surfaces (minimal API contracts) run without the
        // history table — degrade gracefully instead of breaking the write.
        if (! Schema::hasTable('task_status_histories')) {
            return null;
        }

        TaskStatusHistoryLogger::log($task, $fromStatus, $toStatus, $actor->toHistoryActor());

        return TaskStatusHistory::where('task_id', $task->id)->latest('id')->first();
    }

    private function syncCompletedAt(Task $task, string $toStatus): void
    {
        if ($toStatus === 'done') {
            $task->completed_at ??= now();
            $task->saveQuietly();
        } elseif ($task->completed_at !== null) {
            $task->completed_at = null;
            $task->saveQuietly();
        }
    }

    /**
     * Transitions that require input must receive a payload satisfying the
     * configured field rules. Rejected before any persistence.
     */
    private function assertRequiredInput(Task $task, TransitionActor $actor, string $fromStatus, string $toStatus, array $input): void
    {
        foreach ($this->rules('required_input', $fromStatus, $toStatus) as $requirement) {
            $validator = \Illuminate\Support\Facades\Validator::make($input, $requirement['fields']);

            if ($validator->fails()) {
                $exception = InvalidTaskTransition::validationFailed(
                    $fromStatus,
                    $toStatus,
                    $requirement['message'] ?? 'This status change requires additional information: '.$validator->errors()->first(),
                );
                $this->recordBlockedTransition($task, $actor, $exception);

                throw $exception;
            }
        }
    }

    private function recordBlockedTransition(Task $task, TransitionActor $actor, InvalidTaskTransition $exception): void
    {
        $entry = activity('task-workflow')
            ->performedOn($task)
            ->withProperties([
                'stage' => $exception->stage,
                'from_status' => $exception->fromStatus,
                'to_status' => $exception->toStatus,
                'message' => $exception->getMessage(),
            ]);

        if ($actor->isUser() && $actor->userId) {
            $user = User::find($actor->userId);

            if ($user) {
                $entry->causedBy($user);
            }
        }

        $entry->log('transition_blocked');
    }

    /**
     * Rework transitions (sent back for changes, returned to in-progress from
     * review, reopened from done) reset the checklist: the Definition of Done
     * must be re-confirmed before the task can enter review again.
     *
     * Agent/daemon actors are exempt — a daemon failure retry is not rework.
     */
    private function resetReworkChecklists(Task $task, string $fromStatus, string $toStatus, TransitionActor $actor): void
    {
        if ($actor->type === 'daemon') {
            return;
        }

        $isRework = $toStatus === 'changes-requested'
            || ($toStatus === 'in-progress' && in_array($fromStatus, ['in-review', 'changes-requested', 'done'], true));

        if (! $isRework || ! Schema::hasTable('task_checklists')) {
            return;
        }

        $task->checklists()->where('is_checked', true)->update(['is_checked' => false]);
    }

    private function notifyStatusChange(Task $task, string $fromStatus, string $toStatus, TransitionActor $actor): void
    {
        // Agent-initiated transitions broadcast their own dedicated events
        // (AgentTaskStarted/Completed/Failed) at the call site — skip the
        // user-facing notification fan-out for non-user actors.
        if (! $actor->isUser()) {
            return;
        }

        Cache::tags(['dashboard'])->flush();

        $task->loadMissing(['project', 'assignee']);
        $mover = $task->assignee?->getAttribute('id') === $actor->userId ? $task->assignee : \App\Models\User::find($actor->userId);

        if (! $mover) {
            return;
        }

        $recipients = collect();

        if ($task->assigned_to && $task->assigned_to !== $actor->userId) {
            $recipients->push($task->assignee);
        }

        $managers = \App\Models\User::whereHas('roleModel', fn ($q) => $q->whereIn('slug', ['super-admin', 'manager']))->get();
        $recipients = $recipients->merge($managers)->unique('id')->filter(fn ($u) => $u->id !== $actor->userId);

        foreach ($recipients as $recipient) {
            $recipient->notify(new TaskStatusChangedNotification($task, $fromStatus, $toStatus, $mover));
        }

        SlackNotificationHelper::notifyOnce(new TaskStatusChangedNotification($task, $fromStatus, $toStatus, $mover));
    }
}
