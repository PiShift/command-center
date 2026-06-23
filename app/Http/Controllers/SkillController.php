<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (! $team) {
            abort(403);
        }

        $skills = Skill::where('team_id', $team->id)
            ->with(['creator', 'agents'])
            ->orderBy('name')
            ->get()
            ->map(function ($skill) {
                return [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'description' => $skill->description,
                    'agent_count' => $skill->agents->count(),
                    'created_by' => $skill->creator?->name ?? 'Unknown',
                    'updated_at' => $skill->updated_at->diffForHumans(),
                ];
            });

        return view('skills.index', compact('skills'));
    }

    public function create()
    {
        return view('skills.create');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (! $team) {
            abort(403);
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'content'     => ['nullable', 'string'],
        ]);

        $skill = Skill::create([
            'team_id'    => $team->id,
            'name'       => $data['name'],
            'description' => $data['description'] ?? null,
            'content'    => $data['content'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('skills.show', $skill)->with('success', 'Skill created.');
    }

    public function show(Request $request, Skill $skill)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (! $team || $skill->team_id !== $team->id) {
            abort(403);
        }

        $skill->load(['creator', 'agents']);

        return view('skills.show', compact('skill'));
    }

    public function edit(Skill $skill)
    {
        $user = auth()->user();
        $team = $user->teams()->first();

        if (! $team || $skill->team_id !== $team->id) {
            abort(403);
        }

        return view('skills.edit', compact('skill'));
    }

    public function update(Request $request, Skill $skill)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (! $team || $skill->team_id !== $team->id) {
            abort(403);
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'content'     => ['nullable', 'string'],
        ]);

        $skill->update($data);

        return back()->with('success', 'Skill updated.');
    }

    public function destroy(Request $request, Skill $skill)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (! $team || $skill->team_id !== $team->id) {
            abort(403);
        }

        $skill->delete();

        return redirect()->route('skills.index')->with('success', 'Skill deleted.');
    }
}
