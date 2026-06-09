<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\SprintCompletedNotification;
use App\Notifications\SprintPublishedNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SprintController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
        ]);

        $data['project_id'] = $project->id;
        $data['sort_order'] = $project->sprints()->max('sort_order') + 1;
        $data['status']     = 'draft';

        $project->sprints()->create($data);

        return redirect()->route('projects.show', $project)
            ->withFragment('sprints')
            ->with('success', 'Sprint created.');
    }

    public function update(Request $request, Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
        ]);

        $sprint->update($data);

        return redirect()->route('projects.show', $project)
            ->withFragment('sprints')
            ->with('success', 'Sprint updated.');
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $sprint->delete();

        return redirect()->route('projects.show', $project)
            ->withFragment('sprints')
            ->with('success', 'Sprint deleted.');
    }

    public function publish(Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $taskCount = $sprint->tasks()->count();
        if ($taskCount === 0) {
            return redirect()->route('projects.show', $project)
                ->withFragment('sprints')
                ->with('error', 'Add at least one task before publishing.');
        }

        $sprint->publish();

        $taskCount = $sprint->tasks()->count();
        $sprint->load('project');

        // Notify all team members of the project (excluding the publisher)
        $project->load('teams.members');
        $publisherId = auth()->id();
        $notified = collect();
        foreach ($project->teams as $team) {
            foreach ($team->members as $member) {
                if ($member->id !== $publisherId && ! $notified->contains('id', $member->id)) {
                    $member->notify(new SprintPublishedNotification($sprint, $taskCount));
                    $notified->push($member);
                }
            }
        }

        SlackNotificationHelper::notifyOnce(new SprintPublishedNotification($sprint, $taskCount));

        return redirect()->route('projects.show', $project)
            ->withFragment('sprints')
            ->with('success', 'Sprint published. Tasks are now visible to developers.');
    }

    public function unpublish(Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $sprint->unpublish();

        return redirect()->route('projects.show', $project)
            ->withFragment('sprints')
            ->with('success', 'Sprint moved back to draft.');
    }

    public function complete(Request $request, Project $project, Sprint $sprint)
    {
        abort_unless(auth()->user()->hasPermission('projects.edit'), 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $unfinishedTasksQuery = $sprint->tasks()->where('status', '!=', 'done');
        $unfinishedCount = (int) $unfinishedTasksQuery->count();

        $action = $request->input('completion_action');
        $movedTaskCount = 0;
        $movedToSprintName = null;

        if ($unfinishedCount > 0) {
            if (!$action) {
                return redirect()->route('projects.show', $project)
                    ->withFragment('sprints')
                    ->with('error', "This sprint has {$unfinishedCount} unfinished tasks. Please choose how to handle them before completing.");
            }

            if ($action === 'move_existing') {
                $validated = $request->validate([
                    'target_sprint_id' => [
                        'required',
                        'integer',
                        Rule::exists('sprints', 'id')->where(fn ($query) => $query
                            ->where('project_id', $project->id)
                            ->whereIn('status', ['draft', 'active'])
                            ->where('id', '!=', $sprint->id)
                        ),
                    ],
                ]);

                $targetSprint = Sprint::query()->findOrFail((int) $validated['target_sprint_id']);

                $movedTaskCount = (int) $unfinishedTasksQuery->update([
                    'sprint_id' => $targetSprint->id,
                ]);
                $movedToSprintName = $targetSprint->name;
            } elseif ($action === 'create_new') {
                $validated = $request->validate([
                    'new_sprint_name' => ['required', 'string', 'max:255'],
                ]);

                $targetSprint = $project->sprints()->create([
                    'name' => $validated['new_sprint_name'],
                    'description' => null,
                    'deadline' => null,
                    'sort_order' => (int) $project->sprints()->max('sort_order') + 1,
                    'status' => 'draft',
                ]);

                $movedTaskCount = (int) $unfinishedTasksQuery->update([
                    'sprint_id' => $targetSprint->id,
                ]);
                $movedToSprintName = $targetSprint->name;
            } elseif ($action !== 'complete_anyway') {
                return redirect()->route('projects.show', $project)
                    ->withFragment('sprints')
                    ->with('error', 'Invalid completion option selected.');
            }
        }

        $sprint->complete();

        $doneCount = $sprint->tasks()->where('status', 'done')->count();
        $managers  = User::whereHas('roleModel', fn($q) => $q->whereIn('slug', ['super-admin', 'manager']))->get();
        foreach ($managers as $manager) {
            $manager->notify(new SprintCompletedNotification($sprint->load('project'), $doneCount));
        }

        SlackNotificationHelper::notifyOnce(new SprintCompletedNotification($sprint->load('project'), $doneCount));

        if ($movedToSprintName) {
            return redirect()->route('projects.show', $project)
                ->withFragment('sprints')
                ->with('success', "Sprint completed. {$movedTaskCount} tasks moved to {$movedToSprintName}.");
        }

        return redirect()->route('projects.show', $project)
            ->withFragment('sprints')
            ->with('success', 'Sprint marked as completed.');
    }
}
