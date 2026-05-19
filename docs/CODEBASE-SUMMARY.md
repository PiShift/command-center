# Command Center — Codebase Analysis Report

> Generated: 2026-05-19 · Laravel 12 · PHP 8.4

---

## Table of Contents

1. [Projects (Sprints, Backlog, Guide)](#1-projects)
2. [Tasks (Checklist, Attachments, Comments, Guide, Weight, Claim)](#2-tasks)
3. [Users and Roles / Permissions](#3-users-and-roles--permissions)
4. [Teams](#4-teams)
5. [Customers](#5-customers)
6. [Invoices, Payments, and Credits](#6-invoices-payments-and-credits)
7. [Expenses](#7-expenses)
8. [AI (Agents, Controllers, Routes)](#8-ai)
9. [Notifications](#9-notifications)
10. [Dashboard and War Room](#10-dashboard-and-war-room)
11. [Other Controllers / Routes](#11-other-controllers--routes)
12. [Summary: Built vs Partial vs Missing](#12-summary)
13. [Inconsistencies and Technical Debt](#13-inconsistencies-and-technical-debt)
14. [Stack Versions and Key Packages](#14-stack-versions-and-key-packages)

---

## 1. Projects

### Database Tables & Columns

**`projects`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `customer_id` | FK → customers | nullable, nullOnDelete |
| `name` | string | |
| `description` | text | nullable |
| `guide` | longText | nullable — project-level implementation guide |
| `github_repo` | string | nullable |
| `stack` | string | nullable |
| `color` | string(7) | hex colour for War Room UI |
| `status` | enum: active, paused, complete | |
| `start_date` | date | nullable |
| `deadline` | date | nullable |
| `budget` | decimal(10,2) | nullable — `Project::$fillable` does NOT include this |
| `health` | enum: on-track, at-risk, blocked | |
| `created_at`, `updated_at` | timestamps | |

> **Note:** `budget` column exists in the DB (added by `2026_03_06_200001`) but is absent from `Project::$fillable` and is not rendered in any form — effectively unwritable through the app.

**`sprints`** (originally `milestones`, renamed 2026-05-17)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | cascadeOnDelete |
| `name` | string | |
| `description` | text | nullable |
| `deadline` | date | nullable |
| `sort_order` | smallint unsigned | |
| `status` | enum: draft, active, completed | added on rename |
| `created_at`, `updated_at` | timestamps | |

**`backlog_items`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | cascadeOnDelete |
| `sprint_id` | FK → sprints | nullable, nullOnDelete (was `milestone_id`) |
| `title` | string | |
| `description` | text | nullable |
| `guide` | longText | nullable |
| `status` | enum: raw, refined | |
| `promoted` | boolean | |
| `promoted_task_id` | FK → tasks | nullable, nullOnDelete |
| `promoted_at` | timestamp | nullable |
| `sort_order` | smallint unsigned | |
| `created_at`, `updated_at` | timestamps | |

### Models & Relationships

**`Project`**
- `belongsTo` → `Customer`
- `hasMany` → `Task`
- `hasMany` → `Sprint` (ordered by `sort_order`)
- `hasMany` → `BacklogItem` (ordered by `sort_order`)
- `hasMany` → `Conversation` (legacy AI chat sessions)
- `belongsToMany` → `Team` (pivot: `project_team`)
- Helper methods: `isOverdue()`, `openTasksCount()`

**`Sprint`**
- `belongsTo` → `Project`
- `hasMany` → `Task` (FK: `sprint_id`)
- `hasMany` → `BacklogItem` (FK: `sprint_id`)
- Scopes: `draft`, `active`, `completed`
- Methods: `publish()`, `complete()`, `unpublish()`, `isPublishable()`

**`BacklogItem`**
- `belongsTo` → `Project`
- `belongsTo` → `Sprint` (FK: `sprint_id`)
- `belongsTo` → `Task` as `promotedTask`
- Scopes: `pending`, `promoted`

### Routes & Controller Methods

**`ProjectController`** — `Route::resource('projects', …)` + extras:

| Method | Route | Action |
|---|---|---|
| GET | `/projects` | `index` — paginated list, sortable, filterable by status/health/customer; scope-limited for developers |
| GET | `/projects/create` | `create` |
| POST | `/projects` | `store` |
| GET | `/projects/{project}` | `show` — loads full project with teams, tasks, sprints, backlog |
| GET | `/projects/{project}/edit` | `edit` |
| PUT/PATCH | `/projects/{project}` | `update` |
| DELETE | `/projects/{project}` | `destroy` |
| POST | `/projects/{project}/teams/sync` | `assignTeams` |

**`SprintController`**

| Method | Route | Action |
|---|---|---|
| POST | `/projects/{project}/sprints` | `store` |
| PATCH | `/projects/{project}/sprints/{sprint}` | `update` |
| DELETE | `/projects/{project}/sprints/{sprint}` | `destroy` |
| POST | `/projects/{project}/sprints/{sprint}/publish` | `publish` |
| POST | `/projects/{project}/sprints/{sprint}/unpublish` | `unpublish` |
| POST | `/projects/{project}/sprints/{sprint}/complete` | `complete` |

**`BacklogItemController`**

| Method | Route | Action |
|---|---|---|
| POST | `/projects/{project}/backlog` | `store` |
| PATCH | `/projects/{project}/backlog/bulk-sprint` | `bulkSprint` |
| POST | `/projects/{project}/backlog/bulk-promote` | `bulkPromote` |
| DELETE | `/projects/{project}/backlog/bulk-delete` | `bulkDelete` |
| PATCH | `/projects/{project}/backlog/{backlogItem}` | `update` |
| DELETE | `/projects/{project}/backlog/{backlogItem}` | `destroy` |
| POST | `/projects/{project}/backlog/{backlogItem}/promote` | `promote` — creates a Task from a BacklogItem |

### Views

| View | Path |
|---|---|
| Project list | `resources/views/projects/index.blade.php` |
| Create / Edit form | `resources/views/projects/form.blade.php` |
| Project detail | `resources/views/projects/show.blade.php` |
| Backlog item partial | `resources/views/projects/_backlog_item_row.blade.php` |

---

## 2. Tasks

### Database Tables & Columns

**`tasks`** (core + additive migrations)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | cascadeOnDelete |
| `sprint_id` | FK → sprints | nullable, nullOnDelete (was `milestone_id`) |
| `assigned_to` | FK → users | nullable, nullOnDelete |
| `title` | string | |
| `description` | text | nullable |
| `guide` | longText | nullable — AI-generated implementation guide |
| `type` | enum: bug, feature, change | |
| `priority` | enum: low, medium, high | |
| `status` | string (free-form) | dynamically bound to `kanban_columns.slug` |
| `source` | enum: manual, ai-chat | |
| `original_input` | text | nullable |
| `due_date` | date | nullable |
| `estimated_hours` | smallint unsigned | nullable |
| `weight` | tinyint | nullable (1–5 complexity scale) |
| `labels` | json | nullable |
| `completed_at` | timestamp | nullable |
| `created_at`, `updated_at` | timestamps | |

**`task_checklists`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `task_id` | FK → tasks | cascadeOnDelete |
| `label` | string | |
| `is_checked` | boolean | |
| `sort_order` | smallint unsigned | |
| `created_at`, `updated_at` | timestamps | |

**`task_comments`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `task_id` | FK → tasks | cascadeOnDelete |
| `user_id` | FK → users | cascadeOnDelete |
| `body` | text | |
| `created_at`, `updated_at` | timestamps | |

**`media`** (spatie/laravel-medialibrary)  
Stores task attachments (`attachments`, `images` collections) and comment attachments (`attachment` collection — single file). Receipt attachments for expenses are also stored here.

**`kanban_columns`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `slug` | string unique | maps to `tasks.status` |
| `color` | string | Tailwind token |
| `icon` | string | emoji |
| `position` | smallint unsigned | |
| `is_protected` | boolean | `open` column is protected |
| `created_at`, `updated_at` | timestamps | |

Seeded columns (current): `open` (pos 0, protected), `in-progress` (pos 2), `in-review` (pos 3), `done` (pos 4).

> **Note:** The `backlog` column was seeded initially then removed by migration. Tasks that were in backlog status were force-moved to `in-progress` with no business logic applied.

### Models & Relationships

**`Task`** implements `HasMedia` (spatie)
- `belongsTo` → `Project`
- `belongsTo` → `Sprint` (FK: `sprint_id`)
- `belongsTo` → `User` as `assignee` (FK: `assigned_to`)
- `hasMany` → `TaskComment` (with `author`, ordered latest-first)
- `hasMany` → `TaskChecklist` (ordered by `sort_order`)
- Media collections: `attachments` (local disk), `images` (local disk, with 200×200 thumb conversion)
- Helper: `isOverdue()`

**`TaskComment`** implements `HasMedia` (spatie)
- `belongsTo` → `Task`
- `belongsTo` → `User` as `author`
- Media collection: `attachment` (single file, local disk)

**`TaskChecklist`**
- `belongsTo` → `Task`

**`KanbanColumn`**
- `hasMany` → `Task` (FK: `tasks.status` ↔ `kanban_columns.slug`)

### Routes & Controller Methods

**`TaskController`** — `Route::resource('tasks', …)` + extras:

| Method | Route | Action |
|---|---|---|
| GET | `/tasks` | `index` — paginated, filterable by status/type/priority/project/assignee/overdue/high_priority |
| GET | `/tasks/create` | `create` |
| POST | `/tasks` | `store` |
| GET | `/tasks/{task}` | `show` — loads project, assignee, checklists, media, comments with authors/media |
| GET | `/tasks/{task}/edit` | `edit` |
| PUT/PATCH | `/tasks/{task}` | `update` |
| DELETE | `/tasks/{task}` | `destroy` |
| PATCH | `/tasks/{task}/advance` | `advance` — cycles status forward |
| POST | `/tasks/{task}/claim` | `claim` — assigns to self if unassigned |

**`TaskChecklistController`**

| Method | Route | Action |
|---|---|---|
| POST | `/tasks/{task}/checklists` | `store` |
| PATCH | `/tasks/{task}/checklists/{item}` | `update` |
| DELETE | `/tasks/{task}/checklists/{item}` | `destroy` |

**`TaskAttachmentController`**

| Method | Route | Action |
|---|---|---|
| POST | `/tasks/{task}/attachments` | `store` |
| DELETE | `/tasks/{task}/attachments/{media}` | `destroy` |
| GET | `/tasks/{task}/attachments/{media}/download` | `download` |

**`TaskCommentController`**

| Method | Route | Action |
|---|---|---|
| POST | `/tasks/{task}/comments` | `store` |
| DELETE | `/tasks/{task}/comments/{comment}` | `destroy` |

**`TaskCommentAttachmentController`**

| Method | Route | Action |
|---|---|---|
| POST | `/tasks/{task}/comments/{comment}/attachment` | `store` |
| DELETE | `/tasks/{task}/comments/{comment}/attachment` | `destroy` |
| GET | `/tasks/{task}/comments/{comment}/attachment` | `download` |

### Views

| View | Path |
|---|---|
| Task list | `resources/views/tasks/index.blade.php` |
| Create / Edit form | `resources/views/tasks/form.blade.php` |
| Task detail | `resources/views/tasks/show.blade.php` |

### Livewire

**`KanbanBoard`** — mounted at `/board`
- Loads tasks filtered by project, assignee, priority
- Developers scoped to their team's projects; managers see all
- `moveTask(taskId, columnSlug)` — updates `tasks.status`, gated by `TaskPolicy::editStatus`
- `claimTask(taskId)` — assigns self, logs activity

**`TaskModal`** — embedded in board view
- Full inline task editor: title, description, guide, status, priority, project, assignee, due date, estimated hours
- Handles checklist add/check/delete
- Handles comment post

---

## 3. Users and Roles / Permissions

### Database Tables & Columns

**`users`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `email` | string unique | |
| `email_verified_at` | timestamp | nullable |
| `password` | string (hashed) | |
| `role` | string | nullable — **legacy orphan column**, not used for authorization |
| `role_id` | FK → roles | nullable, nullOnDelete |
| `color` | string(7) | hex colour for War Room avatar |
| `initials` | string(3) | nullable |
| `remember_token` | string | |
| `created_at`, `updated_at` | timestamps | |

**`roles`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `slug` | string unique | e.g. `super-admin`, `developer`, `manager` |
| `color` | string(7) | |
| `description` | text | nullable |
| `created_at`, `updated_at` | timestamps | |

**`permissions`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `slug` | string unique | e.g. `tasks.view`, `projects.manage` |
| `group` | string | grouping label |
| `description` | text | nullable |
| `depends_on` | FK → permissions | nullable, nullOnDelete (self-referential) |
| `created_at`, `updated_at` | timestamps | |

**`role_permission`** (pivot)
| Column | Type |
|---|---|
| `role_id` | FK → roles |
| `permission_id` | FK → permissions |

### Models & Relationships

**`User`**
- `hasMany` → `Task` (FK: `assigned_to`)
- `belongsTo` → `Role` as `roleModel` (FK: `role_id`)
- `belongsToMany` → `Team` (pivot: `team_members`)
- `hasPermission(string $slug): bool` — checks via `roleModel->permissions`, short-circuits for `super-admin` slug

**`Role`**
- `belongsToMany` → `Permission` (pivot: `role_permission`)
- `hasMany` → `User`
- `isSuperAdmin(): bool`

**`Permission`**
- `belongsToMany` → `Role`
- Self-referential: `belongsTo` → `Permission` as `parent`; `hasMany` → `Permission` as `children`
- `ancestorSlugs(): array`, `descendantSlugs(): array`

### Routes & Controller Methods

**`Auth/LoginController`**
| Method | Route | Action |
|---|---|---|
| GET | `/login` | `showLogin` |
| POST | `/login` | `login` |
| POST | `/logout` | `logout` |

**`RoleController`** — `Route::resource('roles', …)`:
- Full CRUD: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`

**`UserController`** — `Route::resource('users', …)`:
- Full CRUD: `index`, `create`, `store`, `show` (redirects to edit), `edit`, `update`, `destroy`

### Policies

**`ProjectPolicy`** — registered in `AppServiceProvider`
- `viewAny`, `view` (team-scoped for developers), `manage`

**`TaskPolicy`** — registered in `AppServiceProvider`
- Granular field-level gates: `editStatus`, `editMeta`, `editPriority`, `editProject`, `editAssignee`, `claim`
- Resolution order: super-admin → team leader → `tasks.edit_any` → own task + specific permission

### Views

| View | Path |
|---|---|
| User list | `resources/views/users/index.blade.php` |
| Create / Edit form | `resources/views/users/form.blade.php` |
| Role list | `resources/views/roles/index.blade.php` |
| Role form | `resources/views/roles/form.blade.php` |

---

## 4. Teams

### Database Tables & Columns

**`teams`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `description` | text | nullable |
| `lead_user_id` | FK → users | nullable, nullOnDelete |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft deletes |

**`team_members`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `team_id` | FK → teams | cascadeOnDelete |
| `user_id` | FK → users | cascadeOnDelete |
| `created_at`, `updated_at` | timestamps | |
| Unique | (`team_id`, `user_id`) | |

**`project_team`** (pivot)
| Column | Type |
|---|---|
| `project_id` | FK → projects |
| `team_id` | FK → teams |

### Models & Relationships

**`Team`** (uses `SoftDeletes`)
- `belongsTo` → `User` as `lead`
- `belongsToMany` → `User` via `team_members` (withTimestamps)
- `belongsToMany` → `Project` via `project_team`

### Routes & Controller Methods

**`TeamController`** — `Route::resource('teams', …)`:
- Full CRUD: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`

**`TeamMemberController`**
| Method | Route | Action |
|---|---|---|
| POST | `/teams/{team}/members` | `store` — add user to team |
| DELETE | `/teams/{team}/members/{user}` | `destroy` — remove user from team |

**`ProjectTeamController`**
| Method | Route | Action |
|---|---|---|
| POST | `/projects/{project}/teams` | `store` — assign team to project |
| DELETE | `/projects/{project}/teams/{team}` | `destroy` — remove team from project |

### Views

| View | Path |
|---|---|
| Team list | `resources/views/teams/index.blade.php` |
| Create / Edit form | `resources/views/teams/form.blade.php` |
| Team detail | `resources/views/teams/show.blade.php` |

---

## 5. Customers

### Database Tables & Columns

**`customers`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `email` | string | nullable |
| `phone` | string | nullable |
| `company` | string | nullable |
| `website` | string | nullable |
| `status` | enum: prospect, active, churned | |
| `industry` | string | nullable |
| `avatar_url` | string | nullable |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |

### Models & Relationships

**`Customer`**
- `hasMany` → `Project`
- `hasManyThrough` → `Task` via `Project` (scoped to `status != done`) as `activeTasks`

### Routes & Controller Methods

**`CustomerController`** — `Route::resource('customers', …)`:
- Full CRUD: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`

### Views

| View | Path |
|---|---|
| Customer list | `resources/views/customers/index.blade.php` |
| Create / Edit form | `resources/views/customers/form.blade.php` |
| Customer detail | `resources/views/customers/show.blade.php` |

---

## 6. Invoices, Payments, and Credits

### Database Tables & Columns

**`invoices`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `invoice_number` | string unique | auto-generated: `INV-{year}-{seq}` |
| `customer_id` | FK → customers | cascadeOnDelete |
| `project_id` | FK → projects | nullable, nullOnDelete |
| `issue_date` | date | |
| `due_date` | date | |
| `currency` | string(10) | hardcoded to `MRU` in application |
| `exchange_rate` | decimal(10,4) | default 1.0 |
| `subtotal` | decimal(12,2) | |
| `discount_type` | enum: percent, fixed | nullable |
| `discount_value` | decimal(12,2) | nullable |
| `discount_amount` | decimal(12,2) | computed |
| `tax_rate` | decimal(5,2) | nullable |
| `tax_amount` | decimal(12,2) | computed |
| `total` | decimal(12,2) | computed |
| `amount_paid` | decimal(12,2) | |
| `status` | enum: draft, published, cancelled | lifecycle status |
| `payment_status` | enum: unpaid, partially_paid, paid | payment status (split 2026-05-19) |
| `notes` | longText | nullable |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft deletes |

**`invoice_items`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `invoice_id` | FK → invoices | cascadeOnDelete |
| `type` | enum: manual, task | |
| `task_id` | FK → tasks | nullable, nullOnDelete |
| `description` | text | |
| `quantity` | decimal(10,2) | |
| `unit` | string | hours / days / units / fixed |
| `unit_price` | decimal(12,2) | |
| `discount_type` | enum: percent, fixed | nullable |
| `discount_value` | decimal(12,2) | nullable |
| `subtotal` | decimal(12,2) | computed |
| `sort_order` | integer | |
| `created_at`, `updated_at` | timestamps | |

**`invoice_payments`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `invoice_id` | FK → invoices | cascadeOnDelete |
| `customer_id` | FK → customers | cascadeOnDelete |
| `amount` | decimal(12,2) | |
| `currency` | string(10) | |
| `payment_date` | date | |
| `method` | string | bank_transfer, cash, check, card, other |
| `reference` | string | nullable |
| `proof_path` | string | nullable |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |

**`customer_credits`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `customer_id` | FK → customers | cascadeOnDelete |
| `source_type` | enum: overpayment, manual | |
| `source_id` | bigint unsigned | nullable (polymorphic-style, but no morph helper) |
| `currency` | string(10) | |
| `amount_original` | decimal(12,2) | |
| `amount_remaining` | decimal(12,2) | |
| `status` | enum: available, partially_used, fully_used | |
| `description` | string | |
| `created_at`, `updated_at` | timestamps | |

**`credit_allocations`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `credit_id` | FK → customer_credits | cascadeOnDelete |
| `invoice_id` | FK → invoices | cascadeOnDelete |
| `customer_id` | FK → customers | cascadeOnDelete |
| `amount_applied` | decimal(12,2) | |
| `allocated_at` | timestamp | |
| `notes` | string | nullable |
| `created_at`, `updated_at` | timestamps | |

### Models & Relationships

**`Invoice`** (uses `SoftDeletes`)
- `belongsTo` → `Customer`
- `belongsTo` → `Project`
- `hasMany` → `InvoiceItem` (ordered by `sort_order`)
- `hasMany` → `InvoicePayment` (ordered by `payment_date`)
- `hasMany` → `CreditAllocation`
- Scopes: `draft`, `published`, `unpaid`, `overdue`
- Computed attributes: `amount_due`, `is_overdue`
- Auto-generates `invoice_number` on `creating` hook

**`InvoiceItem`**
- `belongsTo` → `Invoice`
- `belongsTo` → `Task` (nullable)

**`InvoicePayment`**
- `belongsTo` → `Invoice`
- `belongsTo` → `Customer`

**`CustomerCredit`**
- `belongsTo` → `Customer`
- `hasMany` → `CreditAllocation` (FK: `credit_id`)
- Scope: `available`
- Static: `getBalanceForCustomer(customerId, currency): float`

**`CreditAllocation`**
- `belongsTo` → `CustomerCredit` as `credit`
- `belongsTo` → `Invoice`
- `belongsTo` → `Customer`

### Services

**`InvoiceService`**
- `calculateTotals(Invoice)` — computes subtotal → discount → tax → total and saves
- `publish(Invoice)` — validates items exist, transitions `status → published`
- `recordPayment(Invoice, array)` — creates `InvoicePayment`, updates `amount_paid` and `payment_status`, generates `CustomerCredit` on overpayment
- `applyCredit(Invoice, CustomerCredit, amount)` — creates `CreditAllocation`, deducts from credit remaining, updates `amount_paid` + `payment_status`

### Routes & Controller Methods

**`InvoiceController`** — `Route::resource('invoices', …)` + extras:

| Method | Route | Action |
|---|---|---|
| GET | `/invoices` | `index` — paginated, filterable by status/payment_status/overdue/customer |
| GET | `/invoices/create` | `create` |
| POST | `/invoices` | `store` |
| GET | `/invoices/{invoice}` | `show` |
| GET | `/invoices/{invoice}/edit` | `edit` |
| PUT/PATCH | `/invoices/{invoice}` | `update` |
| DELETE | `/invoices/{invoice}` | `destroy` |
| POST | `/invoices/{invoice}/publish` | `publish` |
| PATCH | `/invoices/{invoice}/cancel` | `cancel` |
| PATCH | `/invoices/{invoice}/reset-draft` | `resetToDraft` |
| GET | `/invoices/{invoice}/preview` | `preview` — Blade PDF template in browser |
| GET | `/invoices/{invoice}/download` | `download` — PDF download via dompdf |
| POST | `/invoices/bulk-action` | `bulkAction` — bulk cancel/delete |

**`InvoicePaymentController`**
| Method | Route | Action |
|---|---|---|
| POST | `/invoices/{invoice}/payments` | `store` — delegates to `InvoiceService::recordPayment` |

**`PaymentController`**
| Method | Route | Action |
|---|---|---|
| GET | `/payments` | `index` — global payments ledger |

**`CreditController`**
| Method | Route | Action |
|---|---|---|
| GET | `/customers/{customer}/credits` | `index` — credit balance for a customer |
| POST | `/invoices/{invoice}/apply-credit` | `apply` — delegates to `InvoiceService::applyCredit` |

### Policies

**`InvoicePolicy`** — **NOT registered** in `AppServiceProvider`.  
The policy exists (`app/Policies/InvoicePolicy.php`) but `Gate::policy(Invoice::class, InvoicePolicy::class)` is missing from `AppServiceProvider::boot()`. The `InvoiceController` currently uses direct `abort_unless($user->hasPermission(...))` calls for authorization — the policy is unused at runtime.

### Views

| View | Path |
|---|---|
| Invoice list | `resources/views/invoices/index.blade.php` |
| Create | `resources/views/invoices/create.blade.php` |
| Edit | `resources/views/invoices/edit.blade.php` |
| Shared form partial | `resources/views/invoices/form.blade.php` |
| Invoice detail | `resources/views/invoices/show.blade.php` |
| PDF template | `resources/views/invoices/pdf.blade.php` |
| Payments ledger | `resources/views/payments/index.blade.php` |
| Customer credits | `resources/views/credits/index.blade.php` |

---

## 7. Expenses

### Database Tables & Columns

**`expense_categories`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string unique | |
| `color` | string | hex colour |
| `icon` | string | nullable |
| `is_system` | boolean | system categories cannot be deleted |
| `sort_order` | smallint unsigned | |
| `created_at`, `updated_at` | timestamps | |

**`recurring_charges`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `category_id` | FK → expense_categories | nullable, nullOnDelete |
| `project_id` | FK → projects | nullable, nullOnDelete |
| `amount` | decimal(12,2) | |
| `currency` | string(10) | default `MRU` |
| `frequency` | enum: monthly, quarterly, annual | |
| `start_date` | date | |
| `next_due_date` | date | |
| `is_active` | boolean | |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft deletes |

**`monthly_budgets`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `category_id` | FK → expense_categories | cascadeOnDelete |
| `month` | date | always first day of month |
| `amount` | decimal(12,2) | |
| `currency` | string(10) | default `MRU` |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |
| Unique | (`category_id`, `month`) | |

**`expenses`**
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `title` | string | |
| `category_id` | FK → expense_categories | nullable, nullOnDelete |
| `project_id` | FK → projects | nullable, nullOnDelete |
| `recurring_charge_id` | FK → recurring_charges | nullable, nullOnDelete |
| `amount` | decimal(12,2) | |
| `currency` | string(10) | always `MRU` — locked in model `saving` hook |
| `expense_date` | date | |
| `month` | date | auto-computed from `expense_date` on save |
| `status` | enum: draft, confirmed | |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft deletes |

### Models & Relationships

**`ExpenseCategory`**
- (no relationships defined in model; categories are looked up directly)

**`RecurringCharge`** (uses `SoftDeletes`)
- `belongsTo` → `ExpenseCategory` (FK: `category_id`)
- `belongsTo` → `Project`
- Scopes: `active`, `dueInMonth(Carbon)`
- `computeNextDueDate()` — advances `next_due_date` by frequency period

**`MonthlyBudget`**
- `belongsTo` → `ExpenseCategory` (FK: `category_id`)

**`Expense`** implements `HasMedia` (spatie), uses `SoftDeletes`
- `belongsTo` → `ExpenseCategory` (FK: `category_id`)
- `belongsTo` → `Project`
- `belongsTo` → `RecurringCharge`
- Media collection: `receipt` (single file, jpeg/png/pdf)
- Scopes: `draft`, `confirmed`, `forMonth(Carbon)`
- Model `saving` hook auto-sets `month` and locks `currency = MRU`

### Services

**`ExpenseService`**
- `generateRecurringDrafts(Carbon $month): int` — creates draft `Expense` records for all active `RecurringCharge`s due in the given month (skips existing drafts); advances `next_due_date` on charge
- `confirmExpense(Expense): void` — sets status to `confirmed`

### Routes & Controller Methods

**`ExpenseCategoryController`** — partial resource (index, store, update, destroy):
| Method | Route | Action |
|---|---|---|
| GET | `/expense-categories` | `index` |
| POST | `/expense-categories` | `store` |
| PUT/PATCH | `/expense-categories/{expenseCategory}` | `update` |
| DELETE | `/expense-categories/{expenseCategory}` | `destroy` |

**`RecurringChargeController`** — full resource:
- `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`

**`ExpenseController`** — full resource + extras:
| Method | Route | Action |
|---|---|---|
| GET | `/expenses/monthly-overview` | `monthlyOverview` — budget vs actual by category |
| POST | `/expenses/bulk-confirm` | `bulkConfirm` — confirm multiple drafts |
| POST | `/expenses/generate-drafts` | `generateDrafts` — triggers `ExpenseService::generateRecurringDrafts` |
| GET | `/expenses` | `index` |
| GET | `/expenses/create` | `create` |
| POST | `/expenses` | `store` |
| GET | `/expenses/{expense}` | `show` |
| GET | `/expenses/{expense}/edit` | `edit` |
| PUT/PATCH | `/expenses/{expense}` | `update` |
| DELETE | `/expenses/{expense}` | `destroy` |
| PATCH | `/expenses/{expense}/confirm` | `confirm` — single expense confirm |

**`MonthlyBudgetController`**:
| Method | Route | Action |
|---|---|---|
| GET | `/monthly-budgets` | `index` |
| POST | `/monthly-budgets` | `store` |
| DELETE | `/monthly-budgets/{budget}` | `destroy` |

### Policies

**`ExpensePolicy`** — registered in `AppServiceProvider`:
- `viewAny`, `create`, `update`, `delete` (used via `Gate::authorize`)

### Views

| View | Path |
|---|---|
| Expense list | `resources/views/expenses/index.blade.php` |
| Create | `resources/views/expenses/create.blade.php` |
| Edit | `resources/views/expenses/edit.blade.php` |
| Monthly overview | `resources/views/expenses/monthly-overview.blade.php` |
| Recurring charges list | `resources/views/recurring-charges/index.blade.php` |
| Recurring charge create | `resources/views/recurring-charges/create.blade.php` |
| Recurring charge edit | `resources/views/recurring-charges/edit.blade.php` |
| Recurring charge form partial | `resources/views/recurring-charges/_form.blade.php` |
| Monthly budgets | `resources/views/monthly-budgets/index.blade.php` |
| Expense categories | **EMPTY** — `resources/views/expense-categories/` directory exists but contains no blade files |

---

## 8. AI

### Database Tables & Columns

**`conversations`** (legacy — project-scoped JSON chat sessions)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | cascadeOnDelete |
| `user_id` | FK → users | cascadeOnDelete |
| `type` | enum: text, image, voice | |
| `messages` | json | full message history |
| `final_tasks` | json | nullable |
| `status` | enum: discussing, confirmed | |
| `created_at`, `updated_at` | timestamps | |

**`agent_conversations`** + **`agent_conversation_messages`** (Laravel AI package — `laravel/ai`)  
Standard package tables for conversation threading. The conversation ID is a UUID string.

### AI Agents

All agents use `laravel/ai`, provider `OpenAI`, model `gpt-4.1`.

**`BacklogPlannerAgent`** — implements `Agent`, `HasStructuredOutput`, `HasMiddleware`
- Accepts: a project guide string
- Input: project name + raw notes or backlog item descriptions
- Output (structured JSON): array of sprints, each with `name`, `rationale`, array of items (`title`, `description`, `type`, `suggested_weight`)
- Middleware: `LogAiPrompts`

**`PromoteSuggestionsAgent`** — implements `Agent`, `HasStructuredOutput`
- Accepts: a project guide string
- Output (structured JSON): `type`, `priority`, `weight`, `weight_reason`, `estimated_hours`, `description`

**`TaskGuideAgent`** — implements `Agent` (plain text output)
- Accepts: project guide string + sprint name
- Output: markdown implementation guide for a given task

### Middleware

**`LogAiPrompts`** — logs both the dispatched prompt and the response to Laravel log channel.

### Routes & Controller Methods

**`AiController`**:

| Method | Route | Action |
|---|---|---|
| POST | `/projects/{project}/ai/plan` | `plan` — calls `BacklogPlannerAgent`, rate-limited, returns JSON |
| POST | `/projects/{project}/ai/plan/confirm` | `confirmPlan` — persists AI-planned sprints and backlog items |
| POST | `/projects/{project}/ai/promote-suggestions` | `promoteSuggestions` — calls `PromoteSuggestionsAgent` to enrich a backlog item before promoting to task |
| POST | `/tasks/{task}/ai/generate-guide` | `generateGuide` — calls `TaskGuideAgent`, saves result to `tasks.guide` |

Authorization on `plan` uses a private `authorizeProject` helper. `confirmPlan` uses `abort_unless(hasPermission('projects.manage'))`.

### Dual AI System (Technical Debt)

The old `conversations` table + `Conversation` model pre-dates the `laravel/ai` package integration. The old model is still referenced via `Project::conversations()` but no controller routes use it. The new agent conversations flow through `agent_conversations` / `agent_conversation_messages`. The two systems co-exist without reconciliation.

---

## 9. Notifications

No custom notification classes exist in the codebase (`app/Notifications/` directory does not exist).  
The `User` model uses the `Notifiable` trait (Laravel default), but no notifications are sent anywhere in the application. The `HorizonServiceProvider` has commented-out Horizon alert notification config (mail, SMS, Slack) — all disabled.

**Notifications: not implemented.**

---

## 10. Dashboard and War Room

### Dashboard

**`DashboardController::index`**
- Accessible to all authenticated users except `developer` role (redirected to `/board`)
- Computes: active/blocked/at-risk project counts, open/in-progress/overdue/high-priority task counts, done tasks (last 7 days), active customer count
- Loads authenticated user's assigned open tasks with project
- View: `resources/views/dashboard/index.blade.php`

### War Room

**`WarRoomController::index`**
- Returns an **Inertia** response (`Inertia::render('WarRoom/Index', …)`)
- Loads tasks with `project` (id, name, color) and `assignee` (id, name, initials, color)
- Non-`viewAll` users see only their own tasks
- Passes: `projects`, `tasks`, `team` (user list if permitted), `statuses` (kanban columns), `can` (permission map)

**The `WarRoomController` has no registered route.** No `Route::get('/war-room', …)` exists in `web.php`, meaning the controller is completely unreachable. Additionally, the controller renders an Inertia view (`WarRoom/Index`) while the rest of the app uses plain Blade, implying the War Room frontend was never completed.

**Board (Kanban)**
- Route: `GET /board` → `view('board.index')` (Livewire component)
- View: `resources/views/board/index.blade.php`
- Livewire: `KanbanBoard` component (developer-facing task board with drag/claim)

---

## 11. Other Controllers / Routes

### `MilestoneController`

The file `app/Http/Controllers/MilestoneController.php` exists but **no milestone routes are registered** in `web.php`. This controller pre-dates the rename from milestones → sprints and was never updated or removed.

### Root redirect

```php
GET / → redirect to /dashboard (managers) or /board (developers)
```

### Auth routes

`GET /login`, `POST /login`, `POST /logout` — custom `Auth/LoginController`, no Fortify/Breeze/Jetstream.

---

## 12. Summary

### Fully Built

| Area | Status |
|---|---|
| Projects (CRUD, sprints, backlog, guide, teams) | ✅ Complete |
| Tasks (CRUD, advance, claim, checklist, comments, attachments, guide, weight) | ✅ Complete |
| Kanban board (Livewire, drag-to-column, claim) | ✅ Complete |
| Users (CRUD, role assignment) | ✅ Complete |
| Roles & permissions (CRUD, granular permission slugs, policy enforcement) | ✅ Complete |
| Teams (CRUD, member management, project assignment) | ✅ Complete |
| Customers (CRUD, status, industry) | ✅ Complete |
| Invoices (full lifecycle: draft → published → paid, PDF, discount, tax, multi-currency field) | ✅ Complete |
| Invoice payments (record, proof upload, auto-credit on overpayment) | ✅ Complete |
| Customer credits (apply credit to invoices, balance tracking) | ✅ Complete |
| Expenses (CRUD, receipt upload, confirm workflow) | ✅ Complete |
| Recurring charges (CRUD, soft delete, next_due_date advancement) | ✅ Complete |
| Monthly budgets (CRUD, unique per category+month) | ✅ Complete |
| Expense monthly overview | ✅ Complete |
| AI backlog planner (plan → review → confirm flow) | ✅ Complete |
| AI promote suggestions (enrich backlog item before task creation) | ✅ Complete |
| AI task guide generation | ✅ Complete |
| Dashboard (manager KPI summary) | ✅ Complete |

### Partially Built

| Area | Status |
|---|---|
| War Room | ⚠️ Controller + Inertia setup exists, but no route registered and no Inertia/Vue frontend component found |
| Expense categories views | ⚠️ Controller fully functional, but the `/expense-categories` views directory is empty (managed inline or via another view) |
| `Conversation` (legacy AI chat) | ⚠️ Model and DB table exist but no routes or UI; superseded by `laravel/ai` agent conversations |

### Missing / Not Implemented

| Area | Status |
|---|---|
| Notifications | ❌ No notification classes, no notification UI |
| `budget` field on projects | ❌ Column exists in DB but not in `$fillable`, not in any form |
| `MilestoneController` routes | ❌ Controller file exists but no routes registered |
| Email / password reset | ❌ `password_reset_tokens` table exists but no reset flow implemented |
| User registration | ❌ No self-registration; admin creates accounts |
| War Room frontend (Inertia/Vue) | ❌ No `resources/js/Pages/WarRoom/` component found |

---

## 13. Inconsistencies and Technical Debt

### 1. Orphaned `role` column on `users`
The `users` table has both a `role` string column (added in the War Room migration `2026_05_11`) and a `role_id` FK to the `roles` table. Only `role_id` is used for authorization. The `role` string is still in `User::$fillable` and the `UserController` form likely writes both. This is a dual system with no reconciliation logic.

### 2. `InvoicePolicy` not registered
`app/Policies/InvoicePolicy.php` exists with well-defined gates, but `AppServiceProvider::boot()` does not call `Gate::policy(Invoice::class, InvoicePolicy::class)`. The `InvoiceController` uses `abort_unless(hasPermission(...))` bypassing the policy entirely. The policy is dead code.

### 3. `Milestone` model points to a renamed table
`app/Models/Milestone.php` still references the `milestones` table (implicitly, no `$table` override), but the table was renamed to `sprints`. Any query through `Milestone` will throw a table-not-found error. The model should either be deleted or aliased with `protected $table = 'sprints'` (however `Sprint` already exists for that purpose).

### 4. `MilestoneController` is an orphan
`app/Http/Controllers/MilestoneController.php` has no registered routes. It pre-dates the Milestone → Sprint rename and was never removed.

### 5. Dual AI conversation system
`conversations` table + `Conversation` model (project-scoped JSON sessions) coexist with the `agent_conversations` / `agent_conversation_messages` tables from `laravel/ai`. The old system has no routes or UI; it should be formally deprecated and the table eventually dropped.

### 6. `project.budget` field inaccessible
The `budget` decimal column was added by migration but is absent from `Project::$fillable` and from any project form or controller. The field is effectively write-blocked through Eloquent mass assignment and not displayed anywhere.

### 7. Kanban backlog column removed with a data mutation
Migration `2026_05_17_200000` silently moves all `backlog`-status tasks to `in-progress` before deleting the kanban column. This is a destructive, irreversible migration with no rollback. Tasks that were genuinely in backlog (not yet started) were incorrectly promoted to in-progress.

### 8. `WarRoomController` uses Inertia; rest of app uses Blade
The controller calls `Inertia::render('WarRoom/Index', …)`. The `Livewire/Kanban` board uses Blade + Livewire. The rest of the app is pure Blade. There is no consistent SPA layer. The Inertia dependency (`inertiajs/inertia-laravel`) is installed, suggesting this was a direction that was started but not followed through.

### 9. Mixed authorization patterns
Three different auth patterns are used:
- `abort_unless($user->hasPermission('slug'))` (most controllers)
- `Gate::authorize('action', $model)` (ExpenseController, parts of TaskController, AiController)
- `Gate::policy` registration for Project, Task, Expense (but not Invoice)

No consistent approach is enforced project-wide.

### 10. `customer_credits.source_id` is a pseudo-polymorphic column
The `source_id` column is a plain `bigint`, not a proper Eloquent morph. There is no `source_type`/`source_id` morph map registered. The `source_type` enum is limited to `overpayment` and `manual` but there is no model-level accessor to resolve it.

### 11. `expense-categories` views directory is empty
`resources/views/expense-categories/` exists but has no blade files. The `ExpenseCategoryController` handles CRUD but expense categories are likely managed inline (possibly within the expense index view or a modal), with no dedicated pages.

### 12. `proof_path` on `invoice_payments` is a plain string, not media
Invoice payment proofs are stored as a raw `proof_path` string (file path), while the rest of the app uses `spatie/laravel-medialibrary` for file storage. This is an inconsistency in the file handling approach.

---

## 14. Stack Versions and Key Packages

### Core Framework

| Component | Version |
|---|---|
| PHP | `^8.4` (minimum, PHP 8.4 required) |
| Laravel Framework | `^12.0` |
| Laravel Horizon | `^5.45` (queue monitoring) |
| Laravel Tinker | `^2.10.1` |

### Frontend

| Package | Version | Notes |
|---|---|---|
| Livewire | `*` (latest) | Used for Kanban board and task modal |
| Inertia.js (server) | `^3.1` | Installed, only used in WarRoomController |
| Tightenco/Ziggy | `^2.6` | Named route generation for JS |
| Vite | (package.json) | Asset bundler |

### Key Third-Party Packages

| Package | Version | Purpose |
|---|---|---|
| `laravel/ai` | `^0.6.8` | First-party AI agents, structured output, OpenAI provider |
| `spatie/laravel-activitylog` | `^4.12` | Activity logging (used in KanbanBoard for claim events) |
| `spatie/laravel-data` | `^4.20` | Installed but no `Data` objects found in the codebase — unused |
| `spatie/laravel-medialibrary` | `^11.0` | File attachments for tasks, comments, expenses |
| `barryvdh/laravel-dompdf` | `^3.1` | Invoice PDF generation |
| `predis/predis` | `^3.4` | Redis client for queue / cache |
| `inertiajs/inertia-laravel` | `^3.1` | Installed but barely used (only WarRoomController) |

### Dev Packages

| Package | Version | Purpose |
|---|---|---|
| `laravel/pint` | `^1.24` | Code style fixer |
| `laravel/sail` | `^1.41` | Docker dev environment |
| `laravel/pail` | `^1.2.2` | Log tailing |
| `phpunit/phpunit` | `^11.5.3` | Testing |
| `fakerphp/faker` | `^1.23` | Factory data |
| `nunomaduro/collision` | `^8.6` | Better CLI errors |
