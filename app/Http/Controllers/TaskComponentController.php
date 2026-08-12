<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskComponentController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('projects.manage'), 403);

        $components = TaskComponent::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $usageCounts = Task::query()
            ->selectRaw('component, COUNT(*) as task_count')
            ->whereNotNull('component')
            ->whereRaw("TRIM(component) <> ''")
            ->groupBy('component')
            ->pluck('task_count', 'component');

        $globalNames = $components
            ->pluck('name')
            ->map(fn (string $name) => mb_strtolower(trim($name)))
            ->all();

        $legacyValues = Task::query()
            ->selectRaw('TRIM(component) as component, COUNT(*) as task_count')
            ->whereNotNull('component')
            ->whereRaw("TRIM(component) <> ''")
            ->groupByRaw('TRIM(component)')
            ->orderByDesc('task_count')
            ->orderBy('component')
            ->get()
            ->filter(fn ($row) => ! in_array(mb_strtolower($row->component), $globalNames, true))
            ->values();

        return view('settings.task-components', compact('components', 'usageCounts', 'legacyValues'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('projects.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:task_components,name'],
        ]);

        $name = trim($data['name']);
        if ($name === '') {
            return back()->withErrors(['name' => 'Component name is required.']);
        }

        $nextSort = (int) TaskComponent::query()->max('sort_order') + 1;

        TaskComponent::create([
            'name' => $name,
            'sort_order' => $nextSort,
        ]);

        return back()->with('success', 'Global component added.');
    }

    public function update(Request $request, TaskComponent $taskComponent): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('projects.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:task_components,name,' . $taskComponent->id],
        ]);

        $name = trim($data['name']);
        if ($name === '') {
            return back()->withErrors(['name' => 'Component name is required.']);
        }

        $taskComponent->update(['name' => $name]);

        return back()->with('success', 'Global component updated.');
    }

    public function destroy(TaskComponent $taskComponent): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('projects.manage'), 403);

        $taskComponent->delete();

        return back()->with('success', 'Global component removed. Existing tasks keep their current value until reassigned.');
    }

    public function bulkReassign(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('projects.manage'), 403);

        $data = $request->validate([
            'from_component' => ['required', 'string', 'max:100'],
            'to_component_id' => ['required', 'exists:task_components,id'],
        ]);

        $target = TaskComponent::query()->findOrFail((int) $data['to_component_id']);

        $updated = Task::query()
            ->where('component', $data['from_component'])
            ->update(['component' => $target->name]);

        return back()->with('success', $updated . ' ' . str('task')->plural($updated) . ' reassigned to "' . $target->name . '".');
    }
}
