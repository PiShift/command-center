# Codebase Analysis — Command Center

> Generated from source analysis of migrations, models, controllers, routes, and views.  
> Laravel 12 / PHP 8.4 / PostgreSQL / Livewire 3 / Alpine.js / Tailwind CSS v4

---

## Table of Contents

1. [Projects](#1-projects)
2. [Tasks](#2-tasks)
3. [Users](#3-users)
4. [Teams](#4-teams)
5. [Customers](#5-customers)
6. [Invoices](#6-invoices)
7. [Supporting Systems](#7-supporting-systems)
8. [Summary](#8-summary)

---

## 1. Projects

### Database Tables

#### `projects`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `customer_id` | bigint (FK → customers) | nullable, nullOnDelete |
| `name` | string | |
| `description` | text | nullable |
| `github_repo` | string | nullable |
| `stack` | string | nullable |
| `color` | string(7) | default `#4a90d9` |
| `status` | enum | `active`, `paused`, `complete` — default `active` |
| `start_date` | date | nullable |
| `deadline` | date | nullable |
| `budget` | decimal(10,2) | nullable |
| `health` | enum | `on-track`, `at-risk`, `blocked` — default `on-track` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `project_team` (pivot)
| Column | Type | Notes |
|---|---|---|
| `project_id` | bigint (FK → projects) | composite PK |
| `team_id` | bigint (FK → teams) | composite PK |

### Model — `App\Models\Project`

**Fillable:** `customer_id`, `name`, `description`, `github_repo`, `stack`, `color`, `status`, `start_date`, `deadline`, `budget`, `health`

**Casts:** `start_date` → date, `deadline` → date, `budget` → decimal:2

**Relationships:**
- `customer()` → `BelongsTo(Customer)`
- `tasks()` → `HasMany(Task)`
- `conversations()` → `HasMany(Conversation)`
- `teams()` → `BelongsToMany(Team)` via `project_team`

**Methods:** `isOverdue()`, `openTasksCount()`

### Routes & Controller — `ProjectController`

| Method | Route | Action |
|---|---|---|
| GET | `/projects` | `index` — list with filters |
| GET | `/projects/create` | `create` |
| POST | `/projects` | `store` |
| GET | `/projects/{project}` | `show` — detail with tasks |
| GET | `/projects/{project}/edit` | `edit` |
| PUT/PATCH | `/projects/{project}` | `update` |
| DELETE | `/projects/{project}` | `destroy` |

**Extra controllers:**
- `ProjectTeamController@store` — `POST /projects/{project}/teams`
- `ProjectTeamController@destroy` — `DELETE /projects/{project}/teams/{team}`

### Views

| File | Purpose |
|---|---|
| `resources/views/projects/index.blade.php` | Project list |
| `resources/views/projects/form.blade.php` | Shared create/edit form partial |
| `resources/views/projects/show.blade.php` | Project detail with task list |

---

## 2. Tasks

### Database Tables

#### `tasks`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `project_id` | bigint (FK → projects) | cascadeOnDelete |
| `assigned_to` | bigint (FK → users) | nullable, nullOnDelete |
| `title` | string | |
| `description` | text | nullable |
| `type` | enum | `bug`, `feature`, `change` — default `feature` |
| `priority` | enum | `low`, `medium`, `high` — default `medium` |
| `status` | string | matches `kanban_columns.slug` (e.g. `backlog`, `in-progress`, `in-review`, `done`) |
| `source` | enum | `manual`, `ai-chat` — default `manual` |
| `original_input` | text | nullable — stores raw AI chat input |
| `due_date` | date | nullable |
| `estimated_hours` | unsignedSmallInteger | nullable |
| `labels` | json | nullable |
| `completed_at` | timestamp | nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `task_comments`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `task_id` | bigint (FK → tasks) | cascadeOnDelete |
| `user_id` | bigint (FK → users) | cascadeOnDelete |
| `body` | text | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `kanban_columns`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `name` | string | |
| `slug` | string (unique) | used as `tasks.status` value |
| `color` | string | Tailwind color name token |
| `icon` | string | emoji |
| `position` | unsignedSmallInteger | sort order |
| `is_protected` | boolean | prevents deletion |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Default columns (seeded):** Backlog (0), In Progress (1), In Review (2), Done (3)

### Models

**`App\Models\Task`**

**Fillable:** `project_id`, `assigned_to`, `title`, `description`, `type`, `priority`, `status`, `due_date`, `estimated_hours`, `labels`, `completed_at`, `source`, `original_input`

**Casts:** `due_date` → date, `completed_at` → datetime, `labels` → array

**Relationships:**
- `project()` → `BelongsTo(Project)`
- `assignee()` → `BelongsTo(User, 'assigned_to')`
- `comments()` → `HasMany(TaskComment)` (eager-loads author, ordered latest)

**Methods:** `isOverdue()`

---

**`App\Models\TaskComment`**

**Fillable:** `task_id`, `user_id`, `body`

**Relationships:**
- `task()` → `BelongsTo(Task)`
- `author()` → `BelongsTo(User, 'user_id')`

---

**`App\Models\KanbanColumn`**

**Fillable:** `name`, `slug`, `color`, `icon`, `position`, `is_protected`

**Relationships:**
- `tasks()` → `HasMany(Task, 'status', 'slug')` — non-standard FK join on slug

**Accessors:** `getColorHexAttribute()` — resolves color slug to hex

**Static:** `colorOptions()` — supported palette map

### Routes & Controller — `TaskController`

| Method | Route | Action |
|---|---|---|
| GET | `/tasks` | `index` — filterable list |
| GET | `/tasks/create` | `create` |
| POST | `/tasks` | `store` |
| GET | `/tasks/{task}` | `show` |
| GET | `/tasks/{task}/edit` | `edit` |
| PUT/PATCH | `/tasks/{task}` | `update` |
| DELETE | `/tasks/{task}` | `destroy` |
| PATCH | `/tasks/{task}/advance` | `advance` — moves task to next kanban column |

**Livewire components (board):**
- `App\Livewire\KanbanBoard` — renders all columns with tasks, handles `moveTask()` with `Gate::authorize('editStatus', $task)`
- `App\Livewire\TaskModal` — task detail/edit modal with field-level `Gate::authorize()` guards and `$canEdit` array passed to view

### Views

| File | Purpose |
|---|---|
| `resources/views/tasks/index.blade.php` | Table list of tasks |
| `resources/views/tasks/form.blade.php` | Shared create/edit form |
| `resources/views/tasks/show.blade.php` | Task detail |
| `resources/views/board/index.blade.php` | Kanban board shell (mounts Livewire) |
| `resources/views/livewire/kanban-board.blade.php` | Kanban column + card rendering |
| `resources/views/livewire/task-modal.blade.php` | Task modal with field-level auth gates |

### Authorization — `App\Policies\TaskPolicy`

Registered in `AppServiceProvider` via `Gate::policy(Task::class, TaskPolicy::class)`.

Five-step resolution per method (isSuperAdmin → isTeamLeader → `tasks.edit_any` → assigned + specific permission → deny):

| Policy Method | Permission Checked |
|---|---|
| `editStatus` | `tasks.change_status` |
| `editMeta` | `tasks.edit_meta` |
| `editPriority` | `tasks.edit_priority` |
| `editProject` | `tasks.edit_project` |
| `editAssignee` | `tasks.edit_any` |
| `editDates` | `tasks.edit_dates` |
| `deleteComment` | `tasks.comments.delete` |

---

## 3. Users

### Database Table — `users`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `name` | string | |
| `role_id` | bigint (FK → roles) | nullable, nullOnDelete |
| `role` | string | nullable — legacy display label (not used for auth) |
| `color` | string(7) | default `#D97757` — used in avatar/kanban |
| `initials` | string(3) | nullable |
| `email` | string (unique) | |
| `email_verified_at` | timestamp | nullable |
| `password` | string | hashed |
| `remember_token` | string | nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Model — `App\Models\User`

**Fillable:** `name`, `email`, `password`, `role`, `role_id`, `color`, `initials`

**Relationships:**
- `tasks()` → `HasMany(Task, 'assigned_to')`
- `roleModel()` → `BelongsTo(Role, 'role_id')`
- `teams()` → `BelongsToMany(Team)` via `team_members`

**Methods:** `hasPermission(string $slug)` — checks role permissions; super-admin bypasses

**Casts:** `email_verified_at` → datetime, `password` → hashed

### Routes & Controller — `UserController`

| Method | Route | Action |
|---|---|---|
| GET | `/users` | `index` — with search |
| GET | `/users/create` | `create` |
| POST | `/users` | `store` |
| GET | `/users/{user}/edit` | `edit` |
| PUT/PATCH | `/users/{user}` | `update` |
| DELETE | `/users/{user}` | `destroy` |

Note: `show` route is registered but `UserController` has no `show()` method defined.

**Auth routes:**
- `GET /login` → `LoginController@showLogin`
- `POST /login` → `LoginController@login`
- `POST /logout` → `LoginController@logout`

Root `/` redirects: `developer` role → `/board`, all others → `/dashboard`.

### Views

| File | Purpose |
|---|---|
| `resources/views/users/index.blade.php` | User list |
| `resources/views/users/form.blade.php` | Create/edit form |
| `resources/views/auth/login.blade.php` | Login page |
| `resources/views/dashboard/index.blade.php` | Main dashboard |

---

## 4. Teams

### Database Tables

#### `teams`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `name` | string | |
| `description` | text | nullable |
| `lead_user_id` | bigint (FK → users) | nullable, nullOnDelete |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | SoftDeletes |

#### `team_members`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `team_id` | bigint (FK → teams) | cascadeOnDelete |
| `user_id` | bigint (FK → users) | cascadeOnDelete |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| unique | `(team_id, user_id)` | |

### Model — `App\Models\Team`

Uses `SoftDeletes`.

**Fillable:** `name`, `description`, `lead_user_id`

**Relationships:**
- `lead()` → `BelongsTo(User, 'lead_user_id')`
- `members()` → `BelongsToMany(User)` via `team_members` (with timestamps)
- `projects()` → `BelongsToMany(Project)` via `project_team`

### Routes & Controllers

**`TeamController`:**

| Method | Route | Action |
|---|---|---|
| GET | `/teams` | `index` |
| GET | `/teams/create` | `create` |
| POST | `/teams` | `store` |
| GET | `/teams/{team}` | `show` |
| GET | `/teams/{team}/edit` | `edit` |
| PUT/PATCH | `/teams/{team}` | `update` |
| DELETE | `/teams/{team}` | `destroy` |

**`TeamMemberController`:**
- `POST /teams/{team}/members` → `store` — add member
- `DELETE /teams/{team}/members/{user}` → `destroy` — remove member

### Views

| File | Purpose |
|---|---|
| `resources/views/teams/index.blade.php` | Team list |
| `resources/views/teams/form.blade.php` | Create/edit form |
| `resources/views/teams/show.blade.php` | Team detail with member list |

---

## 5. Customers

### Database Table — `customers`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `name` | string | |
| `email` | string | nullable |
| `company` | string | nullable |
| `phone` | string | nullable |
| `website` | string | nullable |
| `status` | enum | `prospect`, `active`, `churned` — default `prospect` |
| `industry` | string | nullable |
| `avatar_url` | string | nullable |
| `notes` | text | nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Model — `App\Models\Customer`

**Fillable:** `name`, `email`, `company`, `phone`, `website`, `status`, `industry`, `avatar_url`, `notes`

**Relationships:**
- `projects()` → `HasMany(Project)`
- `activeTasks()` → `HasManyThrough(Task, Project)` where status ≠ done

### Routes & Controller — `CustomerController`

| Method | Route | Action |
|---|---|---|
| GET | `/customers` | `index` — with search/filter |
| GET | `/customers/create` | `create` |
| POST | `/customers` | `store` |
| GET | `/customers/{customer}` | `show` — detail with projects and active tasks |
| GET | `/customers/{customer}/edit` | `edit` |
| PUT/PATCH | `/customers/{customer}` | `update` |
| DELETE | `/customers/{customer}` | `destroy` |

**Credits route:**
- `GET /customers/{customer}/credits` → `CreditController@index`

### Views

| File | Purpose |
|---|---|
| `resources/views/customers/index.blade.php` | Customer list |
| `resources/views/customers/form.blade.php` | Create/edit form |
| `resources/views/customers/show.blade.php` | Customer detail with project/task summary |
| `resources/views/credits/index.blade.php` | Credit ledger for a customer |

---

## 6. Invoices

### Database Tables

#### `invoices`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `invoice_number` | string (unique) | auto-generated: `INV-{year}-{seq}` |
| `customer_id` | bigint (FK → customers) | cascadeOnDelete |
| `project_id` | bigint (FK → projects) | nullable, nullOnDelete |
| `issue_date` | date | |
| `due_date` | date | |
| `currency` | string(10) | default `MRU` |
| `exchange_rate` | decimal(10,4) | default 1.0000 |
| `subtotal` | decimal(12,2) | |
| `discount_type` | enum | `percent`, `fixed` — nullable |
| `discount_value` | decimal(12,2) | nullable |
| `discount_amount` | decimal(12,2) | default 0 |
| `tax_rate` | decimal(5,2) | nullable |
| `tax_amount` | decimal(12,2) | default 0 |
| `total` | decimal(12,2) | default 0 |
| `amount_paid` | decimal(12,2) | default 0 |
| `status` | enum | `draft`, `published`, `partially_paid`, `paid`, `cancelled` |
| `notes` | longText | nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | SoftDeletes |

#### `invoice_items`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `invoice_id` | bigint (FK → invoices) | cascadeOnDelete |
| `type` | enum | `manual`, `task` |
| `task_id` | bigint (FK → tasks) | nullable, nullOnDelete |
| `description` | text | |
| `quantity` | decimal(10,2) | |
| `unit` | string | `hours`, `days`, `units`, `fixed` — default `units` |
| `unit_price` | decimal(12,2) | |
| `discount_type` | enum | `percent`, `fixed` — nullable |
| `discount_value` | decimal(12,2) | nullable |
| `subtotal` | decimal(12,2) | auto-computed on save |
| `sort_order` | integer | default 0 |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `invoice_payments`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `invoice_id` | bigint (FK → invoices) | cascadeOnDelete |
| `customer_id` | bigint (FK → customers) | cascadeOnDelete |
| `amount` | decimal(12,2) | |
| `currency` | string(10) | |
| `payment_date` | date | |
| `method` | string | `bank_transfer`, `cash`, `check`, `card`, `other` |
| `reference` | string | nullable |
| `proof_path` | string | nullable — uploaded file path |
| `notes` | text | nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `customer_credits`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `customer_id` | bigint (FK → customers) | cascadeOnDelete |
| `source_type` | enum | `overpayment`, `manual` |
| `source_id` | unsignedBigInteger | nullable — polymorphic source ref |
| `currency` | string(10) | |
| `amount_original` | decimal(12,2) | |
| `amount_remaining` | decimal(12,2) | |
| `status` | enum | `available`, `partially_used`, `fully_used` |
| `description` | string | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `credit_allocations`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | |
| `credit_id` | bigint (FK → customer_credits) | cascadeOnDelete |
| `invoice_id` | bigint (FK → invoices) | cascadeOnDelete |
| `customer_id` | bigint (FK → customers) | cascadeOnDelete |
| `amount_applied` | decimal(12,2) | |
| `allocated_at` | timestamp | |
| `notes` | string | nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Models

**`App\Models\Invoice`** — uses `SoftDeletes`

Relationships:
- `customer()` → `BelongsTo(Customer)`
- `project()` → `BelongsTo(Project)`
- `items()` → `HasMany(InvoiceItem)` ordered by `sort_order`
- `payments()` → `HasMany(InvoicePayment)` ordered by `payment_date`
- `creditAllocations()` → `HasMany(CreditAllocation)`

Scopes: `draft`, `published`, `unpaid`, `overdue`

Accessors: `getAmountDueAttribute()`, `getIsOverdueAttribute()`

Auto-generates `invoice_number` in `booted()`.

---

**`App\Models\InvoiceItem`**

Relationships: `invoice()`, `task()`

Auto-computes `subtotal` in `booted()` before save.

Static: `fromTask(Task $task)` — creates a line item pre-filled from a task.

---

**`App\Models\InvoicePayment`**

Relationships: `invoice()`, `customer()`

Triggers `InvoiceService::syncInvoiceAfterPayment()` in `booted()` after creation.

---

**`App\Models\CustomerCredit`**

Relationships: `customer()`, `allocations()`

Scopes: `available()`

Static: `getBalanceForCustomer(int $customerId, string $currency): float`

---

**`App\Models\CreditAllocation`**

Relationships: `credit()`, `invoice()`, `customer()`

### Routes & Controllers

**`InvoiceController`:**

| Method | Route | Action |
|---|---|---|
| GET | `/invoices` | `index` — with filters, bulk-select bar |
| GET | `/invoices/create` | `create` |
| POST | `/invoices` | `store` |
| GET | `/invoices/{invoice}` | `show` — full detail with payments and credits |
| GET | `/invoices/{invoice}/edit` | `edit` |
| PUT/PATCH | `/invoices/{invoice}` | `update` |
| DELETE | `/invoices/{invoice}` | `destroy` (soft delete) |
| POST | `/invoices/{invoice}/publish` | `publish` |
| PATCH | `/invoices/{invoice}/cancel` | `cancel` |
| PATCH | `/invoices/{invoice}/reset-draft` | `resetToDraft` |
| GET | `/invoices/{invoice}/preview` | `preview` — renders PDF in browser |
| GET | `/invoices/{invoice}/download` | `download` — streams PDF |
| POST | `/invoices/bulk-action` | `bulkAction` — delete/cancel/publish batch |

**`InvoicePaymentController`:**
- `POST /invoices/{invoice}/payments` → `store`

**`PaymentController`:**
- `GET /payments` → `index` — global paginated payment log

**`CreditController`:**
- `GET /customers/{customer}/credits` → `index`
- `POST /invoices/{invoice}/apply-credit` → `apply`

**`App\Services\InvoiceService`** (not a controller, but central):
- Computes totals, applies discounts/tax, syncs `amount_paid` and status after payments, generates PDF via dompdf.

### Views

| File | Purpose |
|---|---|
| `resources/views/invoices/index.blade.php` | Invoice list with filters and bulk-action bar |
| `resources/views/invoices/create.blade.php` | Create shell (includes form partial) |
| `resources/views/invoices/edit.blade.php` | Edit shell (includes form partial) |
| `resources/views/invoices/form.blade.php` | Full invoice form with dynamic line items (Alpine.js) |
| `resources/views/invoices/show.blade.php` | Invoice detail with payment history and credit application |
| `resources/views/invoices/pdf.blade.php` | PDF template rendered by dompdf |
| `resources/views/payments/index.blade.php` | Global payment log |
| `resources/views/credits/index.blade.php` | Customer credit ledger |

---

## 7. Supporting Systems

### Roles & Permissions

**Tables:** `roles`, `permissions`, `role_permission` (pivot)

**Models:** `App\Models\Role`, `App\Models\Permission`

`Role` → `BelongsToMany(Permission)` via `role_permission`

`Permission` supports self-referential `depends_on` (parent/child hierarchy), `ancestorSlugs()`, `descendantSlugs()`.

**Known roles (seeded):** `super-admin`, `manager`, `developer`

**Known permission groups (seeded):** tasks (view, change_status, edit_own, edit_any, edit_meta, edit_priority, edit_project, edit_dates, comments.delete)

**Routes:**
- Full CRUD via `RoleController` at `/roles` (no `show`)

**Views:** `roles/index.blade.php`, `roles/form.blade.php`

### Conversations (AI Chat — partial)

**Table:** `conversations` (project_id, user_id, type, messages JSON, final_tasks JSON, status)

**Model:** `App\Models\Conversation` with `discussing` / `confirmed` scopes

**Status:** Table and model exist; no controller or views are registered for this feature. It was designed for an AI task-generation chat flow but is not surfaced in the UI.

### Activity Log

Three migrations create the `activity_log` table (via `spatie/laravel-activitylog`). No custom controllers or views — logging happens automatically via model events where configured.

---

## 8. Summary

### Fully Built

- **Customers** — full CRUD, profile page with active task and project summary, status/industry tracking.
- **Projects** — full CRUD, detail page, team assignment, health/budget/deadline fields.
- **Tasks** — full CRUD, Kanban board (Livewire), drag-to-move columns, task modal with inline editing, comment system, field-level authorization via `TaskPolicy`.
- **Invoices** — full lifecycle (draft → publish → partially paid → paid → cancelled), line items, per-item discounts, tax, PDF generation and download (dompdf), bulk actions, soft delete.
- **Invoice Payments** — recording payments, automatic invoice status sync, global payment log.
- **Customer Credits** — overpayment credit creation, credit application to invoices, credit ledger view.
- **Roles & Permissions** — role model, permission model with dependency tree, `role_permission` pivot, `TaskPolicy` with 5-step resolution chain, `User::hasPermission()` helper.
- **Teams** — full CRUD, member add/remove, team lead, project assignment, detail page.
- **Authentication** — session login/logout, role-based redirect on root.

### Partially Built

- **Users** — index, create, edit, update, destroy exist. The `show` route is registered but no `show()` method is implemented. No user profile page. The legacy `role` (string) column coexists with the newer `role_id` FK — dual system not fully cleaned up.
- **Dashboard** — `DashboardController@index` exists and the view renders; the actual metrics and widgets shown depend on what the view queries, but there is no dedicated service or query object behind it.
- **War Room** — `WarRoomController@index` exists and war-room fields (`color`, `initials`) were added to users and projects, but no route is registered for the war room and there is no corresponding view.
- **Authorization coverage** — `TaskPolicy` is thorough; no policies exist yet for projects, customers, invoices, or teams (all routes are accessible to any authenticated user).

### Missing / Not Started

- **AI Chat / Conversations** — `Conversation` model and table exist, but there is no controller, route, or view. The feature is entirely non-functional.
- **Invoice: `amount_due` computation** — the `getAmountDueAttribute()` accessor exists on the model but does not account for credit allocations; `amount_paid` only reflects direct payments.
- **Role-based access on non-task resources** — no gates, policies, or middleware guards on projects, customers, invoices, teams, or user management routes.
- **User profile / self-service** — no route for a user to view or edit their own profile.
- **Notifications** — `Notifiable` trait is on `User` but no notification classes or triggers exist.
- **File uploads for payment proofs** — `proof_path` column exists on `invoice_payments` but no file upload handling is implemented in `InvoicePaymentController`.
- **Reporting / analytics** — no reporting views, exports (CSV/Excel), or aggregated statistics pages beyond what the dashboard shows.
- **API** — no API routes, no API authentication (Sanctum/Passport), no JSON endpoints.
