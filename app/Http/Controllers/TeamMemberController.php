<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamMemberAddedNotification;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function store(Request $request, Team $team)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Prevent duplicates (unique constraint will also catch it, but give a friendly message)
        if ($team->members()->where('user_id', $data['user_id'])->exists()) {
            return back()->with('error', 'This user is already a member of the team.');
        }

        $team->members()->attach($data['user_id']);

        $addedUser = User::find($data['user_id']);
        if ($addedUser) {
            $addedUser->notify(new TeamMemberAddedNotification($team, $addedUser));
        }

        return back()->with('success', 'Member added.');
    }

    public function destroy(Team $team, User $user)
    {
        abort_unless(auth()->user()->hasPermission('teams.manage'), 403);

        $team->members()->detach($user->id);

        return back()->with('success', 'Member removed.');
    }
}
