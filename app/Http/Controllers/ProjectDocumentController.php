<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ProjectDocumentController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        Gate::authorize('manage', $project);

        $documents = $project->projectDocuments()
            ->get(['id', 'project_id', 'title', 'content', 'type', 'sort_order', 'created_at', 'updated_at']);

        $documents->transform(function (ProjectDocument $doc) {
            $doc->setAttribute('rendered_html', Str::markdown($doc->content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]));

            return $doc;
        });

        return response()->json(['data' => $documents]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('manage', $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $document = $project->projectDocuments()->create([
            'title' => trim($data['title']),
            'content' => $data['content'],
            'type' => isset($data['type']) && trim((string) $data['type']) !== '' ? trim((string) $data['type']) : null,
            'sort_order' => (int) $project->projectDocuments()->max('sort_order') + 1,
        ]);

        $document->setAttribute('rendered_html', Str::markdown($document->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));

        return response()->json(['data' => $document], 201);
    }

    public function update(Request $request, Project $project, ProjectDocument $doc): JsonResponse
    {
        Gate::authorize('manage', $project);
        abort_unless($doc->project_id === $project->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $doc->update([
            'title' => trim($data['title']),
            'content' => $data['content'],
            'type' => isset($data['type']) && trim((string) $data['type']) !== '' ? trim((string) $data['type']) : null,
        ]);

        $fresh = $doc->fresh();
        $fresh->setAttribute('rendered_html', Str::markdown($fresh->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));

        return response()->json(['data' => $fresh]);
    }

    public function destroy(Project $project, ProjectDocument $doc): JsonResponse
    {
        Gate::authorize('manage', $project);
        abort_unless($doc->project_id === $project->id, 404);

        $doc->delete();

        return response()->json(['ok' => true]);
    }
}
