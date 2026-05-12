<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Permissions are seeded in dependency order — parents before children.
     * Format: [slug, name, group, description, depends_on_slug|null]
     */
    private array $permissions = [
        // ── Tasks ──────────────────────────────────────────────────────────
        ['tasks.view',          'View own tasks',       'Tasks',     'See tasks assigned to the user',                          null],
        ['tasks.view_all',      'View ALL tasks',       'Tasks',     'See every task regardless of assignee',                   'tasks.view'],
        ['tasks.create',        'Create tasks',         'Tasks',     'Add new tasks to any project',                            'tasks.view'],
        ['tasks.edit_own',      'Edit own tasks',       'Tasks',     'Update tasks assigned to the user',                       'tasks.view'],
        ['tasks.edit_any',      'Edit any task',        'Tasks',     'Update tasks owned by anyone',                            'tasks.edit_own'],
        ['tasks.delete',        'Delete tasks',         'Tasks',     'Permanently remove tasks',                                'tasks.edit_any'],
        ['tasks.reassign',      'Reassign tasks',       'Tasks',     'Change the assignee of a task',                           'tasks.edit_any'],
        ['tasks.change_status', 'Move tasks on board',  'Tasks',     'Drag tasks between Kanban columns',                       'tasks.view'],

        // ── Projects ───────────────────────────────────────────────────────
        ['projects.view',       'View own projects',    'Projects',  'See projects the user has tasks in',                      null],
        ['projects.view_all',   'View ALL projects',    'Projects',  'See every project regardless of membership',              'projects.view'],
        ['projects.create',     'Create projects',      'Projects',  'Add new projects',                                        'projects.view'],
        ['projects.edit',       'Edit projects',        'Projects',  'Update project name, stack, color etc.',                  'projects.view'],
        ['projects.delete',     'Delete projects',      'Projects',  'Permanently remove projects',                             'projects.edit'],

        // ── Customers ──────────────────────────────────────────────────────
        ['customers.view',      'View customers',       'Customers', 'Browse the customer list',                                null],
        ['customers.create',    'Create customers',     'Customers', 'Add new customers',                                       'customers.view'],
        ['customers.edit',      'Edit customers',       'Customers', 'Update customer records',                                 'customers.view'],
        ['customers.delete',    'Delete customers',     'Customers', 'Permanently remove customers',                            'customers.edit'],

        // ── Users / Team ───────────────────────────────────────────────────
        ['users.view',          'View team members',    'Users',     'See the team list and member profiles',                   null],
        ['users.create',        'Invite / create users','Users',     'Add new members to the workspace',                        'users.view'],
        ['users.edit',          'Edit user profiles',   'Users',     'Change name, initials, color, role',                      'users.view'],
        ['users.delete',        'Delete users',         'Users',     'Remove members from the workspace',                       'users.edit'],
        ['users.assign_role',   'Assign roles',         'Users',     'Change which role a user holds',                          'users.edit'],

        // ── Roles ──────────────────────────────────────────────────────────
        ['roles.view',          'View roles',           'Roles',     'Browse role definitions',                                 null],
        ['roles.create',        'Create roles',         'Roles',     'Define new roles',                                        'roles.view'],
        ['roles.edit',          'Edit roles',           'Roles',     'Modify role names and assigned permissions',              'roles.view'],
        ['roles.delete',        'Delete roles',         'Roles',     'Remove role definitions',                                 'roles.edit'],
    ];

    public function run(): void
    {
        // First pass: create all without depends_on
        foreach ($this->permissions as [$slug, $name, $group, $desc]) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'group' => $group, 'description' => $desc]
            );
        }

        // Second pass: wire up dependencies
        foreach ($this->permissions as [$slug, , , , $parentSlug]) {
            if ($parentSlug) {
                $parent = Permission::where('slug', $parentSlug)->first();
                Permission::where('slug', $slug)->update(['depends_on' => $parent?->id]);
            }
        }
    }
}
