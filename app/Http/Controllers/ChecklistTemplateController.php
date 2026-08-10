<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\Project;
use Illuminate\Http\Request;

class ChecklistTemplateController extends Controller
{
    public function index()
    {
        $templates = ChecklistTemplate::with(['project:id,name', 'items'])
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return view('checklist-templates.index', compact('templates'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('checklist-templates.form', ['template' => null, 'projects' => $projects]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $template = ChecklistTemplate::create([
            'name' => $data['name'],
            'project_id' => $data['project_id'] ?? null,
            'type' => $data['type'] ?? null,
        ]);

        $this->syncItems($template, $data['items']);

        return redirect()->route('checklist-templates.index')
            ->with('success', 'Checklist template created. It will be attached to new matching tasks.');
    }

    public function edit(ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->load('items');
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('checklist-templates.form', ['template' => $checklistTemplate, 'projects' => $projects]);
    }

    public function update(Request $request, ChecklistTemplate $checklistTemplate)
    {
        $data = $this->validated($request);

        $checklistTemplate->update([
            'name' => $data['name'],
            'project_id' => $data['project_id'] ?? null,
            'type' => $data['type'] ?? null,
        ]);

        $this->syncItems($checklistTemplate, $data['items']);

        return redirect()->route('checklist-templates.index')
            ->with('success', 'Checklist template updated. Changes apply to newly created tasks only.');
    }

    public function destroy(ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->delete();

        return redirect()->route('checklist-templates.index')
            ->with('success', 'Checklist template deleted. Items already attached to tasks are kept.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'type' => 'nullable|in:bug,feature,change',
            'items' => 'required|string|max:10000',
        ]);
    }

    /**
     * Replace the template's items with the given newline-separated list.
     */
    private function syncItems(ChecklistTemplate $template, string $items): void
    {
        $labels = collect(preg_split('/\r\n|\r|\n/', $items))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->unique(fn (string $line) => mb_strtolower($line))
            ->values();

        $template->items()->delete();

        foreach ($labels as $index => $label) {
            $template->items()->create([
                'label' => $label,
                'sort_order' => $index,
            ]);
        }
    }
}
