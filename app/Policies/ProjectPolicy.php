<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        if (! $user->hasPermission('projects.view')) {
            return false;
        }

        // Managers and super-admins can view any project
        if ($user->hasPermission('projects.view_all')) {
            return true;
        }

        // Developers can only view projects where their team is assigned
        return $project->teams()
            ->whereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->exists();
    }

    public function manage(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.manage');
    }
}
