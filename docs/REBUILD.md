# Command Center — Full Rebuild Spec
> Replacing Filament with pure Blade + Alpine.js + Tailwind CSS

---

## Tech Stack (new)

| Layer | Choice |
|---|---|
| Backend | Laravel 11 |
| Frontend | Blade + Alpine.js v3 + Tailwind CSS v4 |
| Reactivity | Livewire v3 (keep for Kanban drag-drop) |
| Auth | Laravel built-in (`Auth::routes()`) |
| DB | MySQL (unchanged) |
| Queue | Horizon (unchanged) |
| Activity Log | Spatie (unchanged) |

---

## Database — unchanged, keep all migrations

### Tables & Key Fields

**users**
- `id`, `name`, `email`, `password`, `role_id`, `color`, `initials`
- Relationships: belongsTo Role, hasMany Tasks (assigned_to)

**roles**
- `id`, `name`, `slug`, `color`, `description`
- slug `super-admin` = full access
- Relationships: belongsToMany Permission, hasMany User

**permissions**
- `id`, `name`, `slug`
- Pivot: `role_permission`

**customers**
- `id`, `name`, `email`, `company`, `phone`, `website`, `status` (active/prospect/churned), `industry`, `avatar_url`, `notes`
- Relationships: hasMany Project

**projects**
- `id`, `customer_id`, `name`, `description`, `github_repo`, `stack`, `color`, `status` (active/paused/complete), `start_date`, `deadline`, `budget`, `health` (on-track/at-risk/blocked)
- Relationships: belongsTo Customer, hasMany Task, hasMany Conversation

**tasks**
- `id`, `project_id`, `assigned_to`, `title`, `description`, `type` (bug/feature/change), `priority` (low/medium/high), `status` (kanban column slug), `due_date`, `estimated_hours`, `labels` (JSON array), `completed_at`, `source` (manual/ai-chat), `original_input`
- Relationships: belongsTo Project, belongsTo User (assignee)

**kanban_columns**
- `id`, `name`, `slug`, `color`, `icon`, `position`, `is_protected`
- slug is used as task.status value
- Default columns: backlog, todo, in-progress, in-review, done

**conversations** — AI chat threads linked to project

---

## Models — keep all, only change User

### User model changes needed
Remove `implements FilamentUser` and `canAccessPanel()` — replace with:
```php
// No Filament interface needed
class User extends Authenticatable
{
    // keep hasPermission(), tasks(), roleModel() as-is
}
```

---

## Permission Slugs (used in hasPermission())

```
tasks.view, tasks.create, tasks.edit_any, tasks.delete
projects.view, projects.create, projects.edit, projects.delete
customers.view, customers.create, customers.edit, customers.delete
users.view, users.create, users.edit, users.delete
roles.view, roles.create, roles.edit, roles.delete
```

---

## Routes — new structure

```
GET  /login                     → Auth\LoginController
POST /login
POST /logout

GET  /                          → redirect → /dashboard
GET  /dashboard                 → DashboardController@index   (War Room)
GET  /board                     → KanbanController@index       (Livewire page)
GET  /projects                  → ProjectController@index
GET  /projects/create           → ProjectController@create
GET  /projects/{project}        → ProjectController@show
GET  /projects/{project}/edit   → ProjectController@edit
PUT  /projects/{project}        → ProjectController@update
DELETE /projects/{project}      → ProjectController@destroy
GET  /tasks                     → TaskController@index
GET  /tasks/create              → TaskController@create
GET  /tasks/{task}              → TaskController@show
GET  /tasks/{task}/edit         → TaskController@edit
PUT  /tasks/{task}              → TaskController@update
DELETE /tasks/{task}            → TaskController@destroy
PATCH /tasks/{task}/advance     → TaskController@advance        (quick status change)
GET  /customers                 → CustomerController@index
GET  /customers/create          → CustomerController@create
GET  /customers/{customer}      → CustomerController@show
GET  /customers/{customer}/edit → CustomerController@edit
PUT  /customers/{customer}      → CustomerController@update
DELETE /customers/{customer}    → CustomerController@destroy
GET  /team                      → UserController@index
GET  /team/create               → UserController@create
GET  /team/{user}/edit          → UserController@edit
PUT  /team/{user}               → UserController@update
DELETE /team/{user}             → UserController@destroy
GET  /roles                     → RoleController@index
GET  /roles/create              → RoleController@create
GET  /roles/{role}/edit         → RoleController@edit
PUT  /roles/{role}              → RoleController@update
DELETE /roles/{role}            → RoleController@destroy

# API (JSON, for Alpine fetch calls)
GET  /api/tasks                 → TaskApiController@index
POST /api/tasks                 → TaskApiController@store
PATCH /api/tasks/{task}/status  → TaskApiController@updateStatus  (Kanban drag)
PATCH /api/tasks/{task}/column  → TaskApiController@moveColumn
GET  /api/projects              → ProjectApiController@index
GET  /api/kanban/columns        → KanbanApiController@index
PATCH /api/kanban/columns/reorder → KanbanApiController@reorder
```

---

## Pages & UI Spec

### Layout Shell
- Sidebar (collapsible, 220px → 60px)
- Top bar (page title, user avatar, notifications)
- Main content area

### Sidebar contents (top → bottom)
```
[Logo full / icon-only when collapsed]
─────────────────
WORKSPACE
  War Room     (grid icon)
  Board        (columns icon)  [badge: open task count]
  Projects     (folder icon)   [badge: project count]
  Team         (people icon)
  Tasks        (clipboard icon) [badge: overdue+high-prio count]
─────────────────
PROJECTS (section)
  • [colored dot] Project name   ← one row per project
─────────────────
[< Collapse] button at bottom
```

### 1. Dashboard / War Room (`/dashboard`)

**Stats row** (5 cards):
| Stat | Value | Description |
|---|---|---|
| Active Projects | count | X blocked · X at-risk |
| Open Tasks | count | X in progress · X high priority |
| Overdue Tasks | count | "Need immediate attention" / "All on time" |
| Done This Week | count | Tasks completed in last 7 days |
| Active Customers | count | X prospects · X churned |

**My Tasks widget** (full-width table):
- Filter by project (dropdown)
- Columns: Title, Project, Status (badge), Priority (badge), Due Date
- Quick action: change status inline
- Highlight overdue rows in red

### 2. Kanban Board (`/board`) — Livewire component

**Tabs**: Board | Projects | Team

**Board tab**:
- Columns from `kanban_columns` table (ordered by position)
- Each column: header (name, count badge), sortable cards via SortableJS
- Card: title, project color dot, assignee initials, priority badge, due date
- Drop card → PATCH /api/tasks/{task}/status
- Drag column → PATCH /api/kanban/columns/reorder
- Add task button per column → quick form (title, project, assignee)
- Add column button → inline form

**Projects tab**:
- Grid of project cards
- Each: project name, color, customer, task progress bar (open/total), health badge, deadline

**Team tab**:
- Grid of team member cards
- Each: avatar/initials, name, role, task count (in-progress + total)

### 3. Projects (`/projects`)

**Index**: table with columns:
- Name (link), Customer, Status badge, Health badge, Open tasks count, Deadline, Budget

**Show**: project detail
- Header: name, color, status, health, customer, github repo link, stack tags
- Stats: total tasks, open, done, in-progress
- Tasks table (filterable by status)
- Edit / Delete buttons (permission gated)

**Create / Edit**: form fields:
- name (required), customer_id (select), description (textarea)
- github_repo, stack (text), color (color picker)
- status (select: active/paused/complete)
- start_date, deadline (date pickers)
- budget (number), health (select: on-track/at-risk/blocked)

### 4. Tasks (`/tasks`)

**Index**: table with:
- Title + project name sub-label, Type badge, Priority badge, Status badge
- Assignee, Due date (red if overdue), Estimated hours
- Actions: View, Advance (quick status toggle), Edit, Delete
- Filters: status, type, priority, project, assignee, overdue toggle, high-priority toggle
- Bulk actions: move to in-progress/in-review/done, assign to, delete

**Create / Edit**: form fields:
- title (required), project_id (select with search), assigned_to (select)
- type (bug/feature/change), priority (low/medium/high), status (kanban column)
- due_date, estimated_hours, labels (tag input), description (textarea)
- source (manual/ai-chat), original_input (textarea, shown only if source=ai-chat)

**Show**: task detail view with all fields + activity log

### 5. Customers (`/customers`)

**Index**: table — name, company, email, status badge, industry, project count

**Show**: customer detail
- Info panel: email, phone, website, industry, notes
- Projects sub-table

**Create / Edit**: form fields:
- name (required), email, company, phone, website
- status (active/prospect/churned), industry, avatar_url, notes (textarea)

### 6. Team / Users (`/team`)

**Index**: table — name, email, role badge (colored), initials, task counts

**Create / Edit**:
- name, email, password (create only), role_id (select)
- color (color picker), initials (auto or manual)

### 7. Roles (`/roles`)

**Index**: table — name, slug, color badge, user count, permission count

**Create / Edit**:
- name, slug (auto-generated), color, description
- Permissions: checkbox grid grouped by resource

---

## Auth

- Login page at `/login` (email + password)
- Redirect after login → `/dashboard`
- Middleware: `auth` on all routes
- Permission middleware: check `hasPermission()` per route group

---

## UI Design Tokens

```css
/* Colors */
--color-bg:         #F5F4EF;   /* sidebar + page bg */
--color-border:     #e5e4df;   /* all borders */
--color-text:       #141413;   /* primary text */
--color-text-muted: #5c5c5a;   /* secondary text */
--color-text-dim:   #a0a09e;   /* labels, captions */
--color-accent:     #D97757;   /* orange: active, badges, primary buttons */
--color-accent-bg:  #fde8de;   /* active item background */
--color-hover:      #eeeee9;   /* hover background */
--color-white:      #ffffff;   /* cards, modals */

/* Sidebar */
--sidebar-width:    220px;
--sidebar-collapsed: 60px;
--sidebar-transition: 0.22s cubic-bezier(0.4, 0, 0.2, 1);

/* Typography */
font-family: 'Inter', sans-serif;
--text-xs: 10px;   /* labels, group headers */
--text-sm: 13px;   /* nav items, body */
--text-base: 14px; /* table content */
--text-lg: 16px;   /* page headings */
```

---

## Status / Badge Colors

```
Task type:     bug=red, feature=blue, change=gray
Task priority: high=red, medium=amber, low=gray
Task status:   backlog=gray, in-progress=blue, in-review=amber, done=green
Project status: active=green, paused=amber, complete=gray
Project health: on-track=green, at-risk=amber, blocked=red
Customer status: active=green, prospect=blue, churned=gray
```

---

## Logos

- `/public/images/logo.svg` — full horizontal logo (sidebar open, 26px height)
- `/public/images/icon-wb-round.webp` — square icon (sidebar collapsed, 30×30px)
- `/public/images/logo.png` — backup raster

---

## Sidebar Collapse Animation

Follow this exact technique:
1. Add class `sidebar-collapsed` to `<html>` via Alpine effect watching store
2. Animate `width` + `min-width` on sidebar element (never toggle `display`)
3. Fade text elements with `max-width: 160px → 0` + `opacity: 1 → 0`
4. `overflow: hidden` on sidebar clips content during shrink
5. All transitions at `0.22s cubic-bezier(0.4, 0, 0.2, 1)` so they move together
6. Arrow icon rotates 180deg via `transform: rotate(180deg)`

---

## File Structure (new)

```
app/
  Http/
    Controllers/
      Auth/LoginController.php
      DashboardController.php
      ProjectController.php
      TaskController.php
      CustomerController.php
      UserController.php
      RoleController.php
      Api/TaskApiController.php
      Api/KanbanApiController.php
    Middleware/
      Authenticate.php
      CheckPermission.php
  Livewire/
    KanbanBoard.php          ← Livewire component for /board
  Models/                    ← unchanged
resources/
  views/
    layouts/
      app.blade.php          ← main shell (sidebar + topbar + slot)
      auth.blade.php         ← login-only layout
    components/
      sidebar.blade.php
      topbar.blade.php
      stat-card.blade.php
      badge.blade.php
      table.blade.php
    auth/
      login.blade.php
    dashboard/
      index.blade.php
    board/
      index.blade.php        ← Livewire mount point
    projects/
      index.blade.php
      show.blade.php
      form.blade.php         ← shared create/edit
    tasks/
      index.blade.php
      show.blade.php
      form.blade.php
    customers/
      index.blade.php
      show.blade.php
      form.blade.php
    team/
      index.blade.php
      form.blade.php
    roles/
      index.blade.php
      form.blade.php
  css/app.css                ← Tailwind v4 entry
  js/app.js                  ← Alpine init
```
