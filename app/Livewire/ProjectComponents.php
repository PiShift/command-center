<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Task;
use Livewire\Component;

/**
 * Inline management of a project's allowed Component values.
 *
 * Uses the same `projects.components` JSON column as the project edit form —
 * a single source of truth, exposed in a more convenient location.
 */
class ProjectComponents extends Component
{
    public Project $project;

    public string $newComponent = '';

    public bool $confirmingRemoval = false;

    public ?int $pendingRemoveIndex = null;

    public int $pendingRemoveUsage = 0;

    public function mount(Project $project): void
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
    }

    public function addComponent(): void
    {
        $this->validate([
            'newComponent' => 'required|string|max:50',
        ]);

        $component = trim($this->newComponent);
        $components = $this->components();

        $exists = collect($components)
            ->contains(fn (string $existing) => mb_strtolower($existing) === mb_strtolower($component));

        if ($exists) {
            $this->addError('newComponent', 'This component already exists.');

            return;
        }

        $components[] = $component;
        $this->persist($components);
        $this->newComponent = '';
    }

    /**
     * Request removal — if tasks use this component, confirm first.
     * The component field is nullable and the task keeps its historical
     * value after removal (we never rewrite existing tasks).
     */
    public function requestRemove(int $index): void
    {
        $components = $this->components();

        if (! isset($components[$index])) {
            return;
        }

        $usage = $this->usageCount($components[$index]);

        if ($usage === 0) {
            $this->removeAt($index);

            return;
        }

        $this->pendingRemoveIndex = $index;
        $this->pendingRemoveUsage = $usage;
        $this->confirmingRemoval = true;
    }

    public function confirmRemove(): void
    {
        if ($this->pendingRemoveIndex !== null) {
            $this->removeAt($this->pendingRemoveIndex);
        }

        $this->cancelRemove();
    }

    public function cancelRemove(): void
    {
        $this->confirmingRemoval = false;
        $this->pendingRemoveIndex = null;
        $this->pendingRemoveUsage = 0;
    }

    private function removeAt(int $index): void
    {
        $components = $this->components();

        if (! isset($components[$index])) {
            return;
        }

        unset($components[$index]);
        $this->persist(array_values($components));
    }

    private function usageCount(string $component): int
    {
        return Task::where('project_id', $this->project->id)
            ->where('component', $component)
            ->count();
    }

    /**
     * @return list<string>
     */
    private function components(): array
    {
        return array_values($this->project->components ?? []);
    }

    /**
     * @param  list<string>  $components
     */
    private function persist(array $components): void
    {
        $this->project->update(['components' => $components ?: null]);
    }

    public function render()
    {
        $components = $this->components();
        $usageCounts = [];

        foreach ($components as $component) {
            $usageCounts[$component] = $this->usageCount($component);
        }

        return view('livewire.project-components', [
            'components' => $components,
            'usageCounts' => $usageCounts,
        ]);
    }
}
