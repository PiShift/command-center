<?php

namespace App\Livewire;

use App\Models\AgentRuntime;
use App\Models\Project;
use App\Models\ProjectResource;
use Livewire\Component;

class ProjectResources extends Component
{
    public Project $project;

    // Form state
    public bool $showForm = false;
    public string $resourceType = 'github_repo';
    public string $gitUrl = '';
    public string $label = '';
    public ?string $runtimeId = null;
    public string $localPath = '';

    public function addResource(): void
    {
        if ($this->resourceType === 'github_repo') {
            $this->validate([
                'gitUrl' => 'required|url|max:2048',
                'label'  => 'nullable|string|max:255',
            ]);

            ProjectResource::create([
                'project_id'    => $this->project->id,
                'resource_type' => 'github_repo',
                'resource_ref'  => ['url' => rtrim(trim($this->gitUrl), '/')],
                'label'         => $this->label ?: null,
                'position'      => 0,
                'created_by'    => auth()->id(),
            ]);
        } else {
            $this->validate([
                'runtimeId' => 'required|uuid|exists:agent_runtimes,id',
                'localPath' => 'required|string|max:1024',
                'label'     => 'nullable|string|max:255',
            ]);

            $runtime = AgentRuntime::find($this->runtimeId);

            // Check for duplicate local directory per daemon
            $exists = ProjectResource::where('project_id', $this->project->id)
                ->where('resource_type', 'local_directory')
                ->whereRaw("resource_ref->>'$.daemon_id' = ?", [$runtime->daemon_id])
                ->exists();

            if ($exists) {
                $this->addError('localPath', 'A local directory is already configured for this daemon on this project.');
                return;
            }

            ProjectResource::create([
                'project_id'    => $this->project->id,
                'resource_type' => 'local_directory',
                'resource_ref'  => [
                    'local_path' => trim($this->localPath),
                    'daemon_id'  => $runtime->daemon_id,
                ],
                'label'         => $this->label ?: null,
                'position'      => 0,
                'created_by'    => auth()->id(),
            ]);
        }

        $this->reset(['showForm', 'gitUrl', 'label', 'runtimeId', 'localPath']);
        $this->resourceType = 'github_repo';
    }

    public function deleteResource(string $id): void
    {
        ProjectResource::where('project_id', $this->project->id)
            ->where('id', $id)
            ->delete();
    }

    public function render()
    {
        $resources = ProjectResource::where('project_id', $this->project->id)
            ->orderBy('resource_type')
            ->orderBy('position')
            ->get();

        $runtimes = AgentRuntime::where('status', 'online')
            ->orderBy('name')
            ->get();

        return view('livewire.project-resources', compact('resources', 'runtimes'));
    }
}
