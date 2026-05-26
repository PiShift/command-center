<?php

namespace App\Livewire;

use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class AiChatPanel extends Component
{
    public bool $isOpen = false;
    public ?int $projectId = null;
    public ?int $conversationId = null;
    public array $messages = [];
    public string $input = '';
    public $projects;

    // Context properties
    public string $projectGuide = '';
    public int $activeSprintsCount = 0;
    public int $backlogCount = 0;
    public int $tasksCount = 0;
    public array $activeSprints = [];         // [{id, name, description}] active only — for action card sprint selector
    public array $selectedSprintIds = [];     // checked sprint IDs
    public array $selectedTaskIds = [];       // checked task IDs
    public array $selectedBacklogIds = [];    // checked backlog item IDs
    public array $availableSprints = [];      // [{id, name, status, task_count}] all sprints
    public array $availableTasks = [];        // [{id, title, status, priority, description, assignee_name}]
    public array $availableBacklogItems = []; // [{id, title, status, description}]
    public string $additionalContext = '';    // content from uploaded file
    public string $contextSummary = '';       // built before AI call
    public bool $isStreaming = false;
    public array $recentConversations = [];

    protected $listeners = [
        'open-ai-chat'     => 'handleOpenEvent',
        'ai-project-hint'  => 'handleProjectHint',
    ];

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->hasPermission('projects.view_all')) {
            $this->projects = Project::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'color']);
        } else {
            $this->projects = Project::where('status', 'active')
                ->whereHas('teams.members', fn ($q) => $q->where('users.id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name', 'color']);
        }

        $this->projectId = null;
    }

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function handleOpenEvent(?int $projectId = null): void
    {
        if (! $this->isOpen) {
            $this->isOpen = true;
        }

        if ($projectId) {
            $this->selectProject($projectId);
        }
    }

    /**
     * Pre-select a project when the page hints at one (does not open the panel).
     */
    public function handleProjectHint(int $projectId): void
    {
        if (! $this->projectId) {
            $this->selectProject($projectId);
        }
    }

    public function selectProject(int $projectId): void
    {
        $project = Project::with(['sprints', 'backlogItems', 'tasks'])->findOrFail($projectId);
        Gate::authorize('view', $project);

        $this->projectId = $projectId;

        // Load context counts and data
        $this->projectGuide       = (string) ($project->guide ?? '');
        $this->activeSprintsCount = $project->sprints->where('status', 'active')->count();
        $this->backlogCount       = $project->backlogItems->where('status', 'pending')->count();
        $this->tasksCount         = $project->tasks->count();

        $this->activeSprints = $project->sprints
            ->where('status', 'active')
            ->values()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'description' => (string) ($s->description ?? '')])
            ->toArray();

        $this->selectedSprintIds  = [];
        $this->selectedTaskIds    = [];
        $this->selectedBacklogIds = [];
        $this->additionalContext  = '';
        $this->contextSummary    = '';

        // All sprints with task counts (for context section)
        $this->availableSprints = Sprint::where('project_id', $projectId)
            ->withCount('tasks')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'status'     => $s->status,
                'task_count' => $s->tasks_count,
            ])
            ->toArray();

        // All tasks with assignee (for context section)
        $this->availableTasks = Task::where('project_id', $projectId)
            ->with('assignee:id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'status', 'priority', 'description', 'assigned_to'])
            ->map(fn ($t) => [
                'id'            => $t->id,
                'title'         => $t->title,
                'status'        => $t->status,
                'priority'      => $t->priority ?? 'medium',
                'description'   => (string) ($t->description ?? ''),
                'assignee_name' => $t->assignee?->name ?? '',
            ])
            ->toArray();

        // All unpromoted backlog items (for context section)
        $this->availableBacklogItems = BacklogItem::where('project_id', $projectId)
            ->where('promoted', false)
            ->orderBy('title')
            ->get(['id', 'title', 'status', 'description'])
            ->map(fn ($b) => [
                'id'          => $b->id,
                'title'       => $b->title,
                'status'      => $b->status,
                'description' => (string) ($b->description ?? ''),
            ])
            ->toArray();

        $conversation = AiConversation::where('project_id', $projectId)
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        if (! $conversation) {
            $conversation = AiConversation::create([
                'project_id' => $projectId,
                'user_id'    => auth()->id(),
            ]);
        }

        $this->conversationId = $conversation->id;
        $this->loadConversation($conversation->id);
        $this->loadRecentConversations();
    }

    public function loadConversation(int $conversationId): void
    {
        $conversation = AiConversation::where('id', $conversationId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->conversationId = $conversation->id;
        $this->projectId      = $conversation->project_id;

        $this->messages = $conversation->messages()
            ->get(['id', 'role', 'content', 'actions', 'created_at'])
            ->map(fn ($m) => [
                'id'         => $m->id,
                'role'       => $m->role,
                'content'    => $m->content,
                'actions'    => $m->actions ? $this->normalizeActions($m->actions) : null,
                'created_at' => $m->created_at->toISOString(),
            ])
            ->toArray();

        $this->loadRecentConversations();
    }

    /**
     * Start a brand-new conversation for the current project.
     */
    public function newChat(): void
    {
        if (! $this->projectId) {
            return;
        }

        $conversation = AiConversation::create([
            'project_id' => $this->projectId,
            'user_id'    => auth()->id(),
        ]);

        $this->conversationId = $conversation->id;
        $this->messages       = [];
        $this->isStreaming     = false;
        $this->loadRecentConversations();
    }

    /**
     * Switch to an existing conversation from the history list.
     */
    public function switchConversation(int $conversationId): void
    {
        $this->loadConversation($conversationId);
    }

    /**
     * Fill the input with a suggested prompt and send immediately.
     */
    public function quickPrompt(string $text): void
    {
        $this->input = $text;
        $this->sendMessage();
    }

    public function removeSprintContext(int $id): void
    {
        $this->selectedSprintIds = array_values(array_filter($this->selectedSprintIds, fn ($v) => (int) $v !== $id));
    }

    public function removeTaskContext(int $id): void
    {
        $this->selectedTaskIds = array_values(array_filter($this->selectedTaskIds, fn ($v) => (int) $v !== $id));
    }

    public function removeBacklogContext(int $id): void
    {
        $this->selectedBacklogIds = array_values(array_filter($this->selectedBacklogIds, fn ($v) => (int) $v !== $id));
    }

    public function clearInput(): void
    {
        $this->input = '';
    }

    public function setAdditionalContext(string $content): void
    {
        $this->additionalContext = $content;
    }

    public function removeAdditionalContext(): void
    {
        $this->additionalContext = '';
    }

    public function sendMessage(): void
    {
        $this->validate(['input' => 'required|string|max:10000']);

        if (! $this->conversationId) {
            return;
        }

        // Build context summary from selected sprints + uploaded file.
        $parts = [];

        if ($this->projectGuide !== '') {
            $parts[] = "## Project Guide\n" . $this->projectGuide;
        }

        if (! empty($this->selectedSprintIds)) {
            $sprints = Sprint::whereIn('id', $this->selectedSprintIds)
                ->where('project_id', $this->projectId)
                ->with(['tasks' => fn ($q) => $q->with('assignee:id,name')])
                ->get();

            $sprintText = $sprints->map(function ($sprint) {
                $header    = "**{$sprint->name}** ({$sprint->status})";
                $taskLines = $sprint->tasks->map(function ($t) {
                    $line = "  - {$t->title} [{$t->status}]";
                    if ($t->assignee) {
                        $line .= " assigned to {$t->assignee->name}";
                    }
                    return $line;
                });
                $body = $taskLines->isNotEmpty()
                    ? "\n  Tasks in this sprint:\n" . $taskLines->join("\n")
                    : '';
                return $header . $body;
            })->join("\n\n");

            if ($sprintText !== '') {
                $parts[] = "## Selected Sprints\n" . $sprintText;
            }
        }

        if (! empty($this->selectedTaskIds)) {
            $tasks = Task::whereIn('id', $this->selectedTaskIds)
                ->where('project_id', $this->projectId)
                ->with('assignee:id,name')
                ->get();

            $taskLines = $tasks->map(function ($t) {
                $line = "- **{$t->title}**";
                if ($t->description) {
                    $line .= ': ' . Str::limit($t->description, 200);
                }
                $line .= " [{$t->status}, {$t->priority}]";
                if ($t->assignee) {
                    $line .= " assigned to {$t->assignee->name}";
                }
                return $line;
            })->join("\n");

            if ($taskLines !== '') {
                $parts[] = "## Selected Tasks\n" . $taskLines;
            }
        }

        if (! empty($this->selectedBacklogIds)) {
            $backlogItems = BacklogItem::whereIn('id', $this->selectedBacklogIds)
                ->where('project_id', $this->projectId)
                ->get();

            $backlogLines = $backlogItems->map(function ($b) {
                $line = "- **{$b->title}**";
                if ($b->description) {
                    $line .= ': ' . Str::limit($b->description, 200);
                }
                $line .= " [{$b->status}]";
                return $line;
            })->join("\n");

            if ($backlogLines !== '') {
                $parts[] = "## Selected Backlog Items\n" . $backlogLines;
            }
        }

        if ($this->additionalContext !== '') {
            $parts[] = "## Additional Context\n" . $this->additionalContext;
        }

        $this->contextSummary = implode("\n\n", $parts);

        $text = trim($this->input);

        // Save user message to DB.
        $userMsg = AiConversationMessage::create([
            'conversation_id' => $this->conversationId,
            'role'            => 'user',
            'content'         => $text,
        ]);

        AiConversation::where('id', $this->conversationId)
            ->update(['last_message_at' => now()]);

        // Append to local messages array for immediate display.
        $this->messages[] = [
            'id'         => $userMsg->id,
            'role'       => 'user',
            'content'    => $text,
            'actions'    => null,
            'created_at' => now()->toISOString(),
        ];

        $this->isStreaming = true;
        $this->clearInput();

        // Tell Alpine.js to start the SSE fetch.
        $this->dispatch('begin-stream',
            projectId:      $this->projectId,
            conversationId: $this->conversationId,
            message:        $text,
            context:        $this->contextSummary,
        );
    }

    /**
     * Called by Alpine.js when the SSE stream completes.
     * Extracts any <actions> block, persists clean content + structured actions.
     */
    public function saveAssistantMessage(string $content): void
    {
        if (! $this->conversationId || trim($content) === '') {
            $this->isStreaming = false;
            return;
        }

        // Extract <actions>…</actions> block if present.
        $actions      = null;
        $cleanContent = $content;

        if (preg_match('/<actions>(.*?)<\/actions>/s', $content, $matches)) {
            $jsonStr = trim($matches[1]);
            // Strip markdown code fences the model may have added.
            $jsonStr = preg_replace('/^```(?:json)?\s*/m', '', $jsonStr);
            $jsonStr = preg_replace('/```\s*$/m', '', $jsonStr);
            $jsonStr = trim($jsonStr);

            $decoded = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['items']) && is_array($decoded['items'])) {
                $actions = $this->normalizeActions($decoded);
            }

            $cleanContent = trim(preg_replace('/<actions>.*?<\/actions>/s', '', $content));
        }

        $message = AiConversationMessage::create([
            'conversation_id' => $this->conversationId,
            'role'            => 'assistant',
            'content'         => $cleanContent,
            'actions'         => $actions,
        ]);

        AiConversation::where('id', $this->conversationId)
            ->update(['last_message_at' => now()]);

        $this->messages[] = [
            'id'         => $message->id,
            'role'       => 'assistant',
            'content'    => $cleanContent,
            'actions'    => $actions,
            'created_at' => now()->toISOString(),
        ];

        $this->isStreaming = false;
    }

    /**
     * Confirm a single AI-suggested action (create the item in the project).
     */
    public function confirmAction(int $messageId, int $actionIndex, array $data): void
    {
        if (! $this->projectId) {
            return;
        }

        $title        = substr(strip_tags((string) ($data['title'] ?? '')), 0, 255);
        $description  = substr(strip_tags((string) ($data['description'] ?? '')), 0, 5000);
        $targetAction = (string) ($data['targetAction'] ?? 'backlog');
        $sprintId     = isset($data['sprintId']) && $data['sprintId'] !== '' ? (int) $data['sprintId'] : null;

        if (empty($title)) {
            return;
        }

        Gate::authorize('view', Project::findOrFail($this->projectId));

        if ($targetAction === 'sprint_create') {
            Sprint::create([
                'project_id'  => $this->projectId,
                'name'        => $title,
                'description' => $description ?: null,
                'status'      => 'draft',
                'sort_order'  => 0,
            ]);
        } else {
            BacklogItem::create([
                'project_id'  => $this->projectId,
                'sprint_id'   => ($targetAction === 'sprint' && $sprintId) ? $sprintId : null,
                'title'       => $title,
                'description' => $description ?: null,
                'status'      => ($targetAction === 'sprint' && $sprintId) ? 'refined' : 'raw',
                'promoted'    => false,
                'sort_order'  => 0,
            ]);
        }

        // Mark confirmed in the local messages array for UI feedback.
        foreach ($this->messages as $msgIdx => $msg) {
            if (($msg['id'] ?? null) === $messageId) {
                $this->messages[$msgIdx]['actions']['items'][$actionIndex]['confirmed'] = true;
                break;
            }
        }
    }

    /**
     * Skip (dismiss) a single AI-suggested action card.
     */
    public function skipAction(int $messageId, int $actionIndex): void
    {
        foreach ($this->messages as $msgIdx => $msg) {
            if (($msg['id'] ?? null) === $messageId) {
                $this->messages[$msgIdx]['actions']['items'][$actionIndex]['skipped'] = true;
                break;
            }
        }
    }

    /**
     * Confirm all remaining (non-confirmed, non-skipped) action items for a message.
     */
    public function confirmAllActions(int $messageId): void
    {
        if (! $this->projectId) {
            return;
        }

        Gate::authorize('view', Project::findOrFail($this->projectId));

        foreach ($this->messages as $msgIdx => $msg) {
            if (($msg['id'] ?? null) !== $messageId) {
                continue;
            }

            $actionType = $msg['actions']['type'] ?? 'backlog';

            foreach ($msg['actions']['items'] as $aIdx => $item) {
                if (! empty($item['confirmed']) || ! empty($item['skipped'])) {
                    continue;
                }

                $title       = substr(strip_tags((string) ($item['title'] ?? '')), 0, 255);
                $description = substr(strip_tags((string) ($item['description'] ?? '')), 0, 5000);

                if (empty($title)) {
                    continue;
                }

                if ($actionType === 'sprints') {
                    Sprint::create([
                        'project_id'  => $this->projectId,
                        'name'        => $title,
                        'description' => $description ?: null,
                        'status'      => 'draft',
                        'sort_order'  => 0,
                    ]);
                } else {
                    BacklogItem::create([
                        'project_id'  => $this->projectId,
                        'sprint_id'   => null,
                        'title'       => $title,
                        'description' => $description ?: null,
                        'status'      => 'raw',
                        'promoted'    => false,
                        'sort_order'  => 0,
                    ]);
                }

                $this->messages[$msgIdx]['actions']['items'][$aIdx]['confirmed'] = true;
            }

            break;
        }
    }

    /**
     * Load the 5 most-recent conversations for the current project into the history list.
     */
    private function loadRecentConversations(): void
    {
        if (! $this->projectId) {
            $this->recentConversations = [];
            return;
        }

        $this->recentConversations = AiConversation::where('project_id', $this->projectId)
            ->where('user_id', auth()->id())
            ->latest('last_message_at')
            ->take(5)
            ->get()
            ->map(function ($conv) {
                $firstMsg = $conv->messages()->where('role', 'user')->first(['content', 'created_at']);
                return [
                    'id'        => $conv->id,
                    'preview'   => $firstMsg
                        ? \Illuminate\Support\Str::limit($firstMsg->content, 60)
                        : 'New conversation',
                    'timestamp' => ($conv->last_message_at ?? $conv->created_at)->diffForHumans(),
                    'active'    => $conv->id === $this->conversationId,
                ];
            })
            ->toArray();
    }

    /**
     * Ensure every action item has confirmed/skipped tracking flags.
     */
    private function normalizeActions(array $decoded): array
    {
        $decoded['items'] = array_map(function (array $item): array {
            return array_merge(['confirmed' => false, 'skipped' => false], $item);
        }, $decoded['items'] ?? []);

        return $decoded;
    }

    public function render()
    {
        return view('livewire.ai-chat-panel');
    }
}
