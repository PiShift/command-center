<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamLeadAssignedNotification;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('teams.view'), 403);

        $teams = Team::with(['lead', 'members'])
                     ->withCount('members')
                     ->orderBy('name')
                     ->get();

        return view('teams.index', compact('teams'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $users = User::orderBy('name')->get(['id', 'name', 'initials', 'color']);
        return view('teams.form', ['team' => null, 'users' => $users, 'members' => collect()]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $data = $request->validate([
            'name'         => 'required|string|max:255|unique:teams,name',
            'description'  => 'nullable|string|max:1000',
            'lead_user_id' => 'nullable|exists:users,id',
            'members'      => 'nullable|array',
            'members.*'    => 'exists:users,id',
        ]);

        $team = Team::create([
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'lead_user_id' => $data['lead_user_id'] ?? null,
        ]);

        if (!empty($data['members'])) {
            $team->members()->sync($data['members']);
        }

        return redirect()->route('teams.show', $team)->with('success', 'Team created.');
    }

    public function show(Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.view'), 403);

        $team->load(['lead', 'members', 'projects']);

        $memberIds = $team->members->pluck('id');

        $availableUsers = User::orderBy('name')
            ->get(['id', 'name', 'initials', 'color'])
            ->filter(fn ($u) => !$memberIds->contains($u->id))
            ->values();

        $teamProjectIds = $team->projects->pluck('id');
        $availableProjects = \App\Models\Project::orderBy('name')
            ->get(['id', 'name', 'status', 'color'])
            ->filter(fn ($p) => !$teamProjectIds->contains($p->id))
            ->values();

        return view('teams.show', compact('team', 'availableUsers', 'availableProjects'));
    }

    public function edit(Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $team->load('members');
        $users = User::orderBy('name')->get(['id', 'name', 'initials', 'color']);
        $members = $team->members;

        return view('teams.form', compact('team', 'users', 'members'));
    }

    public function update(Request $request, Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $data = $request->validate([
            'name'         => 'required|string|max:255|unique:teams,name,' . $team->id,
            'description'  => 'nullable|string|max:1000',
            'lead_user_id' => 'nullable|exists:users,id',
            'members'      => 'nullable|array',
            'members.*'    => 'exists:users,id',
        ]);

        $oldLeadId = $team->lead_user_id;

        $team->update([
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'lead_user_id' => $data['lead_user_id'] ?? null,
        ]);

        $team->members()->sync($data['members'] ?? []);

        // Notify new lead if changed
        if (! empty($data['lead_user_id']) && $data['lead_user_id'] != $oldLeadId) {
            $newLead = User::find($data['lead_user_id']);
            if ($newLead) {
                $newLead->notify(new TeamLeadAssignedNotification($team));
            }
        }

        return redirect()->route('teams.show', $team)->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        // Detach from projects (pivot only — does NOT remove users from projects)
        $team->projects()->detach();
        $team->members()->detach();
        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }
}
