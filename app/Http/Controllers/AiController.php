<?php

namespace App\Http\Controllers;

use App\Ai\Agents\BacklogPlannerAgent;
use App\Ai\Agents\PromoteSuggestionsAgent;
use App\Ai\Agents\TaskGuideAgent;
use App\Models\BacklogItem;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class AiController extends Controller
{
    /**
     * POST /projects/{project}/ai/plan
     * Accept notes or backlog item IDs, return a structured sprint plan.
     */
    public function plan(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $data = $request->validate([
            'raw_notes'  => ['nullable', 'string', 'max:20000'],
            'item_ids'   => ['nullable', 'array'],
            'item_ids.*' => ['integer', 'exists:backlog_items,id'],
        ]);

        if (empty($data['raw_notes']) && empty($data['item_ids'])) {
            return response()->json(['error' => 'Provide raw_notes or item_ids.'], 422);
        }

        if (! empty($data['item_ids'])) {
            $items = BacklogItem::whereIn('id', $data['item_ids'])
                ->where('project_id', $project->id)
                ->get();
            $input = $items->map(fn ($item) => "- {$item->title}: {$item->description}")->implode("\n");
        } else {
            $input = $data['raw_notes'];
        }

        try {
            $agent    = new BacklogPlannerAgent($project->guide ?? '');
            $response = $agent->prompt("Project: {$project->name}\n\nPlanning input:\n{$input}");

            return response()->json($response->toArray());
        } catch (Throwable) {
            return response()->json(['error' => 'AI is currently unavailable. Please try again.'], 503);
        }
    }

    /**
     * POST /projects/{project}/ai/plan/confirm
     * Persist the reviewed sprint plan as sprints + backlog items.
     */
    public function confirmPlan(Request $request, Project $project): RedirectResponse
    {
        abort_unless(
            $request->user()->hasPermission('projects.manage'),
            403,
            'You do not have permission to manage this project.'
        );

        $rawSprints = $request->input('sprints');
        if (is_string($rawSprints)) {
            $decoded = json_decode($rawSprints, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return back()->withErrors(['sprints' => 'Invalid plan data. Please try again.']);
            }
            $request->merge(['sprints' => $decoded]);
        }

        $data = $request->validate([
            'sprints'                       => ['required', 'array', 'min:1', 'max:10'],
            'sprints.*.name'                => ['required', 'string', 'max:255'],
            'sprints.*.rationale'           => ['nullable', 'string', 'max:1000'],
            'sprints.*.items'               => ['required', 'array'],
            'sprints.*.items.*.title'       => ['required', 'string', 'max:255'],
            'sprints.*.items.*.description' => ['nullable', 'string', 'max:5000'],
            'sprints.*.items.*.type'        => ['nullable', 'in:feature,bug,change'],
            'sprints.*.items.*.weight'      => ['nullable', 'integer', 'min:1', 'max:5'],
            'sprints.*.items.*.priority'    => ['nullable', 'in:low,medium,high'],
        ]);

        $sprintCount = 0;
        $itemCount   = 0;

        foreach ($data['sprints'] as $sortOrder => $sprintData) {
            $sprint = Sprint::create([
                'project_id'  => $project->id,
                'name'        => $sprintData['name'],
                'description' => $sprintData['rationale'] ?? null,
                'sort_order'  => $sortOrder,
                'status'      => 'draft',
            ]);
            $sprintCount++;

            foreach ($sprintData['items'] as $itemSortOrder => $itemData) {
                BacklogItem::create([
                    'project_id'  => $project->id,
                    'sprint_id'   => $sprint->id,
                    'title'       => $itemData['title'],
                    'description' => $itemData['description'] ?? null,
                    'status'      => 'refined',
                    'promoted'    => false,
                    'sort_order'  => $itemSortOrder,
                ]);
                $itemCount++;
            }
        }

        Conversation::create([
            'project_id'  => $project->id,
            'user_id'     => $request->user()->id,
            'type'        => 'text',
            'status'      => 'confirmed',
            'messages'    => [['role' => 'user', 'content' => $request->input('raw_input', '')]],
            'final_tasks' => null,
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->withFragment('backlog')
            ->with('success', "{$sprintCount} sprint(s) and {$itemCount} backlog items created. Review and promote items when ready.");
    }

    /**
     * POST /projects/{project}/ai/promote-suggestions
     * Return suggested weight, hours, and description for a backlog item.
     */
    public function promoteSuggestions(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $prompt = "Backlog item title: {$data['title']}\nDescription: " . ($data['description'] ?? '');

        try {
            $agent    = new PromoteSuggestionsAgent($project->guide ?? '');
            $response = $agent->prompt($prompt);

            return response()->json($response->toArray());
        } catch (Throwable) {
            return response()->json(['error' => 'AI is currently unavailable. Please try again.'], 503);
        }
    }

    /**
     * POST /tasks/{task}/ai/generate-guide
     * Generate a markdown implementation guide for a task.
     */
    public function generateGuide(Request $request, Task $task): JsonResponse
    {
        abort_unless(
            $request->user()->hasPermission('tasks.edit_own') || $request->user()->hasPermission('tasks.edit_any'),
            403,
            'You do not have permission to edit this task.'
        );

        if (! $this->withinRateLimit($request->user()->id)) {
            return response()->json(['error' => 'Rate limit reached. Please wait a moment before trying again.'], 429);
        }

        $task->load(['project', 'sprint']);
        $project    = $task->project;
        $sprintName = $task->sprint?->name ?? '';

        $prompt = implode("\n", [
            "Task: {$task->title}",
            "Type: {$task->type} | Priority: {$task->priority}",
            "Sprint: {$sprintName}",
            "Description: " . ($task->description ?? ''),
        ]);

        try {
            $agent    = new TaskGuideAgent($project->guide ?? '', $sprintName);
            $response = $agent->prompt($prompt);

            return response()->json(['guide' => (string) $response]);
        } catch (Throwable) {
            return response()->json(['error' => 'AI is currently unavailable. Please try again.'], 503);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless(
            $request->user()->hasPermission('projects.manage'),
            403,
            'You do not have permission to manage this project.'
        );

        abort_unless(
            $this->withinRateLimit($request->user()->id),
            429,
            'Rate limit reached. Please wait a moment before trying again.'
        );
    }

    private function withinRateLimit(int $userId): bool
    {
        return RateLimiter::attempt(
            'ai:' . $userId,
            20,
            fn () => true,
            60
        );
    }
}
