# Dynamic Roles & Permissions System

## Overview

Users are assigned a **Role**. Roles hold any combination of **Permissions**. Permissions are seeded (never manually created), grouped by resource, and have dependency relationships enforced at the UI level.

The string `users.role` column is replaced by `users.role_id` (FK → `roles`).

---

## Database Schema

### `roles`
| column | type | notes |
|---|---|---|
| `id` | bigIncrements | |
| `name` | string | e.g. "Developer" |
| `slug` | string unique | e.g. "developer" |
| `color` | string(7) | hex, default `#4a90d9` |
| `description` | text nullable | |
| `timestamps` | | |

### `permissions`
| column | type | notes |
|---|---|---|
| `id` | bigIncrements | |
| `name` | string | e.g. "Edit any task" |
| `slug` | string unique | e.g. "tasks.edit_any" |
| `group` | string | e.g. "Tasks" |
| `description` | text nullable | |
| `depends_on` | FK → permissions.id nullable | self-ref dependency |
| `timestamps` | | |

### `role_permission` (pivot)
| column | type |
|---|---|
| `role_id` | FK → roles.id |
| `permission_id` | FK → permissions.id |

### `users` changes
- **Add**: `role_id` (FK → `roles.id`, nullable)
- **Drop**: `role` string column (after data migration)

---

## Permission Catalogue

### Tasks
| slug | label | depends on |
|---|---|---|
| `tasks.view` | View own tasks | — |
| `tasks.view_all` | View ALL tasks | `tasks.view` |
| `tasks.create` | Create tasks | `tasks.view` |
| `tasks.edit_own` | Edit own tasks | `tasks.view` |
| `tasks.edit_any` | Edit any task | `tasks.edit_own` |
| `tasks.delete` | Delete tasks | `tasks.edit_any` |
| `tasks.reassign` | Reassign tasks | `tasks.edit_any` |
| `tasks.change_status` | Move tasks on board | `tasks.view` |

### Projects
| slug | label | depends on |
|---|---|---|
| `projects.view` | View own projects | — |
| `projects.view_all` | View ALL projects | `projects.view` |
| `projects.create` | Create projects | `projects.view` |
| `projects.edit` | Edit projects | `projects.view` |
| `projects.delete` | Delete projects | `projects.edit` |

### Customers
| slug | label | depends on |
|---|---|---|
| `customers.view` | View customers | — |
| `customers.create` | Create customers | `customers.view` |
| `customers.edit` | Edit customers | `customers.view` |
| `customers.delete` | Delete customers | `customers.edit` |

### Users / Team
| slug | label | depends on |
|---|---|---|
| `users.view` | View team members | — |
| `users.create` | Invite / create users | `users.view` |
| `users.edit` | Edit user profiles | `users.view` |
| `users.delete` | Delete users | `users.edit` |
| `users.assign_role` | Assign roles to users | `users.edit` |

### Roles
| slug | label | depends on |
|---|---|---|
| `roles.view` | View roles | — |
| `roles.create` | Create roles | `roles.view` |
| `roles.edit` | Edit roles & permissions | `roles.view` |
| `roles.delete` | Delete roles | `roles.edit` |

---

## Dependency Enforcement Rules

- **Granting** a permission automatically grants all ancestors in the dependency chain.
- **Revoking** a permission automatically revokes all descendants that depend on it.
- The UI (Filament + Vue) enforces this visually with disabled checkboxes.

---

## Default Seeded Roles

| Role | Key Permissions |
|---|---|
| **Super Admin** | All permissions |
| **Manager** | `*.view_all`, `tasks.edit_any`, `tasks.delete`, `tasks.reassign`, `users.view`, `projects.edit`, `customers.edit` |
| **Developer** | `tasks.view`, `tasks.edit_own`, `tasks.change_status`, `tasks.create`, `projects.view`, `customers.view` |
| **Viewer** | `tasks.view`, `projects.view`, `customers.view`, `users.view` |

---

## Permission Check in Code

```php
// On User model
$user->hasPermission('tasks.delete'); // bool

// Super Admin shortcut (role slug = 'super-admin') bypasses all checks
```

---

## War Room Controller — `$can` Array

Passed to Inertia on every War Room page load:

```php
'can' => [
    'viewAll'      => $user->hasPermission('tasks.view_all'),
    'create'       => $user->hasPermission('tasks.create'),
    'editAny'      => $user->hasPermission('tasks.edit_any'),
    'delete'       => $user->hasPermission('tasks.delete'),
    'reassign'     => $user->hasPermission('tasks.reassign'),
    'seeTeam'      => $user->hasPermission('users.view'),
    'changeStatus' => $user->hasPermission('tasks.change_status'),
]
```

---

## Filament Resources

### `/admin/roles` — RoleResource
- **Table**: name, color badge, permission count, user count, created_at
- **Form**:
  - Name, slug (auto-generated), color picker, description
  - Permissions section: grouped by resource (Tasks, Projects, Customers, Users, Roles)
    - Each group is a collapsible `Section`
    - `CheckboxList` with dependency logic via Livewire reactive state

### `/admin/users` — UserResource
- **Table**: color dot + initials avatar, name, email, role badge, created_at
- **Form**:
  - Name, email, password (hashed), role (select), color, initials

---

## Vue Component `can` Prop Contract

All War Room components receive `can` as an object (replaces `isAdmin: Boolean`):

```js
defineProps({
  can: {
    type: Object,
    default: () => ({
      viewAll: false, create: false, editAny: false,
      delete: false, reassign: false, seeTeam: false, changeStatus: false
    })
  }
})
```

| `v-if` before | `v-if` after |
|---|---|
| `v-if="isAdmin"` (delete button) | `v-if="can.delete"` |
| `v-if="isAdmin"` (reassign select) | `v-if="can.reassign"` |
| `v-if="isAdmin"` (team tab) | `v-if="can.seeTeam"` |
| `v-if="isAdmin"` (member filter) | `v-if="can.seeTeam"` |
| `v-if="isAdmin"` (add task modal assignee) | `v-if="can.reassign"` |

---

## Implementation Checklist

- [ ] `2026_05_12_create_roles_permissions_tables.php` migration
- [ ] `2026_05_12_add_role_id_to_users.php` migration  
- [ ] `app/Models/Permission.php`
- [ ] `app/Models/Role.php`
- [ ] `app/Models/User.php` — add `hasPermission()`, update fillable
- [ ] `database/seeders/PermissionSeeder.php`
- [ ] `database/seeders/RoleSeeder.php`
- [ ] `database/seeders/DatabaseSeeder.php` — call both seeders
- [ ] `app/Filament/Resources/RoleResource.php` + Pages
- [ ] `app/Filament/Resources/UserResource.php` + Pages
- [ ] `app/Http/Controllers/WarRoomController.php` — `$can` array
- [ ] `resources/js/Pages/WarRoom/Index.vue` — `can` prop
- [ ] `resources/js/Components/WarRoom/CardViewer.vue` — `can` prop
- [ ] `resources/js/Components/WarRoom/AddTaskModal.vue` — `can` prop
