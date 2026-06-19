<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Task;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load(['roleModel', 'twoFactor', 'devices', 'personalAccessTokens', 'loginHistory']);

        // ── Stats ─────────────────────────────────────────────────────────────

        $baseQuery = Task::where('assigned_to', $user->id)->where('status', 'done');

        $tasksCompletedAllTime = (clone $baseQuery)->count();

        $tasksCompletedThisMonth = (clone $baseQuery)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        $currentActiveTasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'open', 'backlog'])
            ->count();

        $avgWeight = round((clone $baseQuery)->avg('weight') ?? 0, 1);

        $sprintsParticipated = (clone $baseQuery)
            ->whereNotNull('sprint_id')
            ->distinct()
            ->count('sprint_id');

        // Most active project (most completed tasks)
        $mostActiveProjectRow = (clone $baseQuery)
            ->selectRaw('project_id, COUNT(*) as cnt')
            ->groupBy('project_id')
            ->orderByDesc('cnt')
            ->with('project:id,name')
            ->first();
        $mostActiveProject = $mostActiveProjectRow?->project?->name ?? '—';

        // Login history (last 10)
        $loginHistory = $user->loginHistory->take(10);
        $agents = Agent::query()
            ->where('owner_id', $user->id)
            ->whereNull('archived_at')
            ->with(['runtime:id,name,provider,status', 'team:id,name'])
            ->orderBy('name')
            ->get();

        return view('profile.show', compact(
            'user',
            'tasksCompletedAllTime',
            'tasksCompletedThisMonth',
            'currentActiveTasks',
            'avgWeight',
            'sprintsParticipated',
            'mostActiveProject',
            'loginHistory',
            'agents',
        ));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'color'    => ['nullable', 'string', 'max:20'],
            'initials' => ['nullable', 'string', 'max:4'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => $request->input('password')]);

        return back()->with('success', 'Password updated.');
    }

    public function updateNotifications(Request $request)
    {
        $data = $request->validate([
            'notification_preferences' => ['required', 'array'],
        ]);

        $request->user()->update($data);

        return response()->json(['ok' => true]);
    }

    public function revokeDevice(Request $request, UserDevice $device)
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }
        $device->delete();
        return back()->with('success', 'Device revoked.');
    }

    public function revokeAllDevices(Request $request)
    {
        $request->user()->devices()->delete();
        return back()->with('success', 'All devices revoked.');
    }
}
