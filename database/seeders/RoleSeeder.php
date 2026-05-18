<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ──────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'color' => '#E74C3C', 'description' => 'Full unrestricted access to everything.']
        );
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // ── Manager ───────────────────────────────────────────────────────
        $manager = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'color' => '#9B59B6', 'description' => 'Sees everything, manages tasks and team — cannot manage roles.']
        );
        $manager->permissions()->sync(
            Permission::whereIn('slug', [
                'tasks.view', 'tasks.view_all', 'tasks.create',
                'tasks.edit_own', 'tasks.edit_any', 'tasks.delete',
                'tasks.reassign', 'tasks.change_status',
                'tasks.edit_meta', 'tasks.edit_priority', 'tasks.edit_project', 'tasks.edit_dates',
                'tasks.comments.delete',
                'projects.view', 'projects.view_all', 'projects.create', 'projects.edit', 'projects.manage',
                'customers.view', 'customers.create', 'customers.edit',
                'users.view', 'users.create', 'users.edit', 'users.assign_role',
                'invoices.view', 'invoices.manage',
                'payments.view',
                'teams.view', 'teams.manage',
                'roles.view',
            ])->pluck('id')
        );

        // ── Developer ─────────────────────────────────────────────────────
        $developer = Role::firstOrCreate(
            ['slug' => 'developer'],
            ['name' => 'Developer', 'color' => '#27AE60', 'description' => 'Board-only access. Sees only assigned tasks, projects, and team.']
        );
        $developer->permissions()->sync(
            Permission::whereIn('slug', [
                'tasks.view',
                'tasks.change_status',
            ])->pluck('id')
        );

        // ── Viewer ────────────────────────────────────────────────────────
        $viewer = Role::firstOrCreate(
            ['slug' => 'viewer'],
            ['name' => 'Viewer', 'color' => '#95A5A6', 'description' => 'Read-only access. Cannot create, edit or delete anything.']
        );
        $viewer->permissions()->sync(
            Permission::whereIn('slug', [
                'tasks.view',
                'projects.view',
                'customers.view',
                'users.view',
                'invoices.view',
                'payments.view',
            ])->pluck('id')
        );
    }
}
