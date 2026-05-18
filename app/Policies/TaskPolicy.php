<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;

class TaskPolicy
{
    /**
     * Resolution order for every field-level action:
     *  1. Super-admin role → always allowed
     *  2. User is lead_user_id of any team whose project contains this task → allowed
     *  3. User has tasks.edit_any → allowed (manager-level override)
     *  4. Task is directly assigned to this user AND they hold the specific permission
     *  5. Deny
     */

    // ── Status ────────────────────────────────────────────────────────────────
    public function editStatus(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))            return true;
        if ($this->isTeamLeader($user, $task))     return true;
        if ($user->hasPermission('tasks.edit_any')) return true;
        if ($task->assigned_to === $user->id && $user->hasPermission('tasks.change_status')) return true;
        return false;
    }

    // ── Title & Description ───────────────────────────────────────────────────
    public function editMeta(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))            return true;
        if ($this->isTeamLeader($user, $task))     return true;
        if ($user->hasPermission('tasks.edit_any')) return true;
        if ($task->assigned_to === $user->id && $user->hasPermission('tasks.edit_meta')) return true;
        return false;
    }

    // ── Priority ──────────────────────────────────────────────────────────────
    public function editPriority(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))            return true;
        if ($this->isTeamLeader($user, $task))     return true;
        if ($user->hasPermission('tasks.edit_any')) return true;
        if ($task->assigned_to === $user->id && $user->hasPermission('tasks.edit_priority')) return true;
        return false;
    }

    // ── Project ───────────────────────────────────────────────────────────────
    public function editProject(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))            return true;
        if ($this->isTeamLeader($user, $task))     return true;
        if ($user->hasPermission('tasks.edit_any')) return true;
        if ($task->assigned_to === $user->id && $user->hasPermission('tasks.edit_project')) return true;
        return false;
    }

    // ── Assignee ──────────────────────────────────────────────────────────────
    public function editAssignee(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))            return true;
        if ($this->isTeamLeader($user, $task))     return true;
        if ($user->hasPermission('tasks.edit_any')) return true;
        if ($task->assigned_to === $user->id && $user->hasPermission('tasks.reassign')) return true;
        return false;
    }

    // ── Due Date & Estimated Hours ─────────────────────────────────────────────
    public function editDates(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))            return true;
        if ($this->isTeamLeader($user, $task))     return true;
        if ($user->hasPermission('tasks.edit_any')) return true;
        if ($task->assigned_to === $user->id && $user->hasPermission('tasks.edit_dates')) return true;
        return false;
    }

    // ── Claim (self-assignment) ───────────────────────────────────────────────
    // Allowed when: task is unassigned, user is not super-admin, and user can view tasks.
    // Super-admins and managers use the regular editAssignee flow instead.
    public function claim(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))             return false;
        if ($task->assigned_to !== null)            return false;
        if (! $user->hasPermission('tasks.view'))   return false;
        return true;
    }

    // ── Delete Comment ────────────────────────────────────────────────────────
    // Only super-admin or users with tasks.comments.delete. Own-comment deletion
    // is intentionally NOT allowed by default — must be granted explicitly.
    public function deleteComment(User $user, Task $task): bool
    {
        if ($this->isSuperAdmin($user))                       return true;
        if ($user->hasPermission('tasks.comments.delete'))    return true;
        return false;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isSuperAdmin(User $user): bool
    {
        $role = $user->relationLoaded('roleModel') ? $user->roleModel : $user->roleModel;
        return $role?->isSuperAdmin() ?? false;
    }

    /**
     * True if the user is the lead_user_id of ANY team whose project list
     * contains this task's project.
     */
    private function isTeamLeader(User $user, Task $task): bool
    {
        if (! $task->project_id) return false;

        return Team::where('lead_user_id', $user->id)
            ->whereHas('projects', fn ($q) => $q->where('projects.id', $task->project_id))
            ->exists();
    }
}
