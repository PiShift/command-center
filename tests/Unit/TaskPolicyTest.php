<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    public function test_super_admin_can_delete_task_without_explicit_permission(): void
    {
        $policy = new TaskPolicy();
        $user = $this->makeUserWithRole('super-admin');

        $this->assertTrue($policy->delete($user, new Task()));
    }

    public function test_user_with_tasks_delete_permission_can_delete_task(): void
    {
        $policy = new TaskPolicy();
        $user = $this->makeUserWithRole('manager', collect(['tasks.delete']));

        $this->assertTrue($policy->delete($user, new Task()));
    }

    public function test_user_without_delete_permission_cannot_delete_task(): void
    {
        $policy = new TaskPolicy();
        $user = $this->makeUserWithRole('manager', collect(['tasks.view']));

        $this->assertFalse($policy->delete($user, new Task()));
    }

    private function makeUserWithRole(string $roleSlug, ?Collection $permissionSlugs = null): User
    {
        $permissionSlugs ??= collect();

        $permissions = $permissionSlugs
            ->map(fn (string $slug) => tap(new Permission(), fn (Permission $permission) => $permission->slug = $slug));

        $role = new Role();
        $role->slug = $roleSlug;
        $role->setRelation('permissions', $permissions);

        $user = new User();
        $user->setRelation('roleModel', $role);

        return $user;
    }
}
