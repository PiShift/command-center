<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal status transitions
    |--------------------------------------------------------------------------
    |
    | The single, explicit map of allowed task status transitions. Any
    | transition not listed here is rejected by TaskStatusService,
    | regardless of who attempts it or through which code path.
    |
    | Keys are current `tasks.status` values; values are the statuses the
    | task may move to. Statuses match `kanban_columns.slug`.
    |
    */
    'transitions' => [
        'open' => ['todo', 'in-progress'],
        'todo' => ['in-progress'],
        'in-progress' => ['in-review'],
        'in-review' => ['changes-requested', 'done', 'in-progress'], // in-progress: agent failure retry
        'changes-requested' => ['in-progress'],
        'done' => ['in-progress'], // reopen — manager-only, see conditions
    ],

    /*
    |--------------------------------------------------------------------------
    | Conditions (actor-level rules per transition)
    |--------------------------------------------------------------------------
    |
    | Each condition receives the actor and the task and must return true
    | for the transition to proceed. Evaluated in order, after legality.
    |
    | Signature: function (TransitionActor $actor, Task $task, string $from, string $to): bool
    |
    */
    'conditions' => [
        // Changes may only be requested by reviewer-level users
        [
            'to' => 'changes-requested',
            'when' => fn (\App\Support\Workflow\TransitionActor $actor) => $actor->hasPermission('tasks.edit_any'),
            'message' => 'Only reviewers or managers can request changes.',
        ],
        // Reopening a done task is manager-only (for correcting mistakes)
        [
            'from' => 'done',
            'to' => 'in-progress',
            'when' => fn (\App\Support\Workflow\TransitionActor $actor) => $actor->hasPermission('tasks.edit_any'),
            'message' => 'Only a manager can reopen a completed task.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Required input per transition
    |--------------------------------------------------------------------------
    |
    | Transitions listed here cannot be performed without the given input —
    | e.g. moving a task into changes-requested requires a category and an
    | explanation, which are recorded as a TaskChangeRequest. Any code path
    | that cannot supply the input (e.g. a board drag) is rejected with the
    | given message instead of silently skipping the requirement.
    |
    | `fields` is a Laravel-style validation rule map for the input payload.
    |
    */
    'required_input' => [
        [
            'to' => 'changes-requested',
            'fields' => [
                'category' => ['required', 'in:Incomplete,Doesn\'t match spec,Bug / broken,Unprofessional / careless,Other'],
                'explanation' => ['required', 'string', 'min:3'],
            ],
            'message' => 'Requesting changes requires a reason: use the "Request changes" action to pick a category and explain what needs to change.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validators (task-data rules per transition)
    |--------------------------------------------------------------------------
    |
    | Each validator receives the task and returns an error message string
    | when the task data does not satisfy the transition, or null when it
    | passes. Evaluated in order, after conditions.
    |
    | Signature: function (Task $task, string $from, string $to): ?string
    |
    */
    'validators' => [
        // Definition of Done gate: all checklist items must be checked
        // before a task can move into review.
        [
            'to' => 'in-review',
            'validate' => function (\App\Models\Task $task): ?string {
                if (! \Illuminate\Support\Facades\Schema::hasTable('task_checklists')) {
                    return null;
                }

                $unchecked = $task->checklists()->where('is_checked', false)->pluck('label');

                if ($unchecked->isEmpty()) {
                    return null;
                }

                return 'Cannot move to In Review: '.$unchecked->count().' unchecked checklist '
                    .\Illuminate\Support\Str::plural('item', $unchecked->count())
                    .' — '.$unchecked->join(', ');
            },
        ],
    ],

];
