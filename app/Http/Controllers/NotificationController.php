<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'all');

        $query = auth()->user()->notifications()->latest();

        $typeMap = [
            'tasks'    => ['task_assigned', 'task_status_changed', 'task_comment', 'task_overdue', 'task_checklist_completed', 'task_claimed'],
            'sprints'  => ['sprint_published', 'sprint_deadline', 'sprint_completed'],
            'projects' => ['project_health'],
            'teams'    => ['team_member_added', 'team_lead_assigned'],
        ];

        if ($tab === 'unread') {
            $query->whereNull('read_at');
        } elseif (isset($typeMap[$tab])) {
            $query->whereIn('data->type', $typeMap[$tab]);
        }

        $notifications = $query->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications', 'tab'));
    }

    public function markRead(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
