<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Skill;
use Illuminate\Http\Request;

class AgentSkillController extends Controller
{
    public function attach(Request $request, Agent $agent)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (! $team || $agent->team_id !== $team->id) {
            abort(403);
        }

        $data = $request->validate([
            'skill_ids' => ['required', 'array'],
            'skill_ids.*' => ['uuid'],
        ]);

        $skills = Skill::whereIn('id', $data['skill_ids'])
            ->where('team_id', $team->id)
            ->pluck('id');

        $agent->skills()->syncWithoutDetaching($skills);

        return back()->with('success', 'Skills added to agent.');
    }

    public function detach(Request $request, Agent $agent, Skill $skill)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (! $team || $agent->team_id !== $team->id || $skill->team_id !== $team->id) {
            abort(403);
        }

        $agent->skills()->detach($skill->id);

        return back()->with('success', 'Skill removed from agent.');
    }
}
