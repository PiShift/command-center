<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\BacklogItem;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class QuickCapture extends Component
{
    public bool $isOpen = false;
    public ?int $selectedProjectId = null;
    public string $captureText = '';
    public string $captureType = 'task';
    public array $projects = [];

    protected $listeners = [
        'open-quick-capture' => 'open',
        'quick-capture-project-hint' => 'handleProjectHint',
    ];

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->projects = [];
            return;
        }

        if ($user->hasPermission('projects.view_all')) {
            $collection = Project::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'color']);
        } else {
            $collection = Project::where('status', 'active')
                ->whereHas('teams.members', fn ($q) => $q->where('users.id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name', 'color']);
        }

        $this->projects = $collection
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => (string) $project->name,
                'color' => (string) ($project->color ?? '#D97757'),
            ])
            ->toArray();

        if (! empty($this->projects)) {
            $this->selectedProjectId = (int) $this->projects[0]['id'];
        }
    }

    public function open(?int $projectId = null, string $text = ''): void
    {
        if ($projectId !== null) {
            $this->selectedProjectId = $this->resolveProjectId($projectId);
        }

        if ($text !== '') {
            $this->captureText = trim($text);
        }

        if (! $this->selectedProjectId && ! empty($this->projects)) {
            $this->selectedProjectId = (int) $this->projects[0]['id'];
        }

        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->captureText = '';
        $this->captureType = 'task';
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedProjectId = $this->resolveProjectId($projectId);
    }

    public function setType(string $type): void
    {
        $this->captureType = in_array($type, ['bug', 'feature', 'task'], true) ? $type : 'task';
    }

    public function handleProjectHint(int $projectId): void
    {
        $resolved = $this->resolveProjectId($projectId);

        if ($resolved) {
            $this->selectedProjectId = $resolved;
        }
    }

    public function saveAsTask(): void
    {
        $title = trim($this->captureText);

        if ($title === '' || ! $this->selectedProjectId) {
            return;
        }

        $project = Project::find($this->selectedProjectId);

        if (! $project) {
            return;
        }

        Gate::authorize('view', $project);

        $taskType = match ($this->captureType) {
            'bug' => 'bug',
            'feature' => 'feature',
            default => 'change',
        };

        BacklogItem::create([
            'project_id' => $project->id,
            'sprint_id' => null,
            'title' => mb_substr($title, 0, 255),
            'description' => null,
            'guide' => null,
            'status' => 'raw',
            'sort_order' => ((int) BacklogItem::where('project_id', $project->id)->max('sort_order')) + 1,
            'promoted' => false,
        ]);

        $this->dispatch('quick-capture-toast', message: "Task saved to {$project->name}.");
        $this->close();
    }

    public function discussWithAi(): void
    {
        $text = trim($this->captureText);

        if ($text === '' || ! $this->selectedProjectId) {
            return;
        }

        $project = Project::find($this->selectedProjectId);

        if (! $project) {
            return;
        }

        Gate::authorize('view', $project);

        $this->isOpen = false;
        $this->dispatch('open-ai-chat', projectId: $project->id);
        $this->dispatch('quick-capture-ai-seed', projectId: $project->id, text: $text);

        $this->captureText = '';
        $this->captureType = 'task';
    }

    private function resolveProjectId(?int $projectId): ?int
    {
        if (! $projectId) {
            return $this->selectedProjectId;
        }

        foreach ($this->projects as $project) {
            if ((int) $project['id'] === (int) $projectId) {
                return (int) $projectId;
            }
        }

        return $this->selectedProjectId;
    }

    public function render()
    {
        return view('livewire.quick-capture');
    }
}
