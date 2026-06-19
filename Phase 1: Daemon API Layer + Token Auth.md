
# Command Center — Phase 1: Daemon API Layer + Token Auth

## Overview
Add a complete daemon API layer to Command Center so the forked Multica daemon can connect to it instead of Multica cloud. This covers: database migrations, token authentication, all daemon API endpoints, task queue population, runtime sweeper, and the token management UI on the user profile page.

This is entirely new, isolated code. Nothing existing is modified except two small additions to `TaskController` and two new columns on existing tables.

---

## Step 1 — Database Migrations

### New table: `daemon_tokens`
```
id                bigint PK
user_id           FK → users, cascadeOnDelete
token_hash        string unique (SHA-256 hex of raw token, never store raw)
name              string (e.g. "MacBook Pro")
last_used_at      timestamp nullable
expires_at        timestamp nullable
created_at        timestamps
updated_at        timestamps
```

### New table: `agent_runtimes`
```
id                uuid PK (use Str::uuid())
user_id           FK → users, nullOnDelete nullable
daemon_id         string (persistent UUID daemon generates for itself)
name              string (e.g. "claude (Bechirs-MacBook-Pro)")
provider          string (claude, codex, gemini, etc.)
status            enum: online, offline — default offline
device_info       string nullable
cli_version       string nullable
launched_by       string nullable (desktop, cli, etc.)
last_seen_at      timestamp nullable
metadata          json nullable
created_at        timestamps
updated_at        timestamps
unique index on   (daemon_id, provider)
```

### New table: `agent_task_queue`
```
id                uuid PK
task_id           FK → tasks, cascadeOnDelete
runtime_id        uuid FK → agent_runtimes, nullOnDelete nullable
status            enum: queued, dispatched, running, waiting, completed, failed, cancelled — default queued
prompt            longText nullable
output            longText nullable
error_message     text nullable
pr_url            string nullable
claimed_at        timestamp nullable
started_at        timestamp nullable
completed_at      timestamp nullable
created_at        timestamps
updated_at        timestamps
```

### Additions to existing tables

Add to `users`:
- `is_agent` boolean default false
- `agent_cli` string nullable

Add to `projects`:
- `repos` json nullable — array of `{url, description, stack, local_path}`

---

## Step 2 — Models

### `DaemonToken` model
- `belongsTo` → `User`
- Hidden: `token_hash`

### `AgentRuntime` model
- `belongsTo` → `User`
- Casts: `metadata` → array
- Scopes: `online()`, `offline()`

### `AgentTaskQueue` model
- `belongsTo` → `Task`
- `belongsTo` → `AgentRuntime`
- Add static method `buildPrompt(Task $task): string` — assembles a clean markdown prompt from task title, description, guide, and checklist items:

```
# {task title}

## Description
{task description}

## Implementation Guide
{task guide if present}

## Checklist
- {item 1}
- {item 2}
...
```

---

## Step 3 — Token Authentication

### Token generation helper
Create `app/Services/DaemonTokenService.php`:

- `generate(): array` — generates `mdt_` + 40 random hex chars as raw token, returns `['raw' => '...', 'hash' => sha256(raw)]`
- `hash(string $token): string` — returns `hash('sha256', $token)`

Token format must be: `mdt_` + 40 lowercase hex characters (20 random bytes encoded as hex)

### Middleware: `DaemonTokenMiddleware`
Create `app/Http/Middleware/DaemonTokenMiddleware.php`:

1. Read `Authorization` header, extract Bearer token
2. If missing or not starting with `mdt_` → return `401 {"error": "unauthorized"}`
3. Hash the token using `hash('sha256', $token)`
4. Check Laravel cache key `daemon_token:{hash}` — if hit, decode cached `{user_id}` and continue
5. If cache miss: query `daemon_tokens` where `token_hash = hash` and (`expires_at` is null OR `expires_at` > now)
6. If not found → return `401 {"error": "unauthorized"}`
7. Update `last_used_at` = now on the token record
8. Cache `daemon_token:{hash}` → `{user_id}` for 10 minutes using Laravel Cache
9. Set authenticated user on request: `auth()->setUser($token->user)`
10. Continue request

---

## Step 4 — API Routes

Add to `routes/api.php` under prefix `/api/daemon`, middleware `DaemonTokenMiddleware`:

```php
Route::prefix('daemon')->middleware(DaemonTokenMiddleware::class)->group(function () {
    Route::post('register', [DaemonController::class, 'register']);
    Route::post('deregister', [DaemonController::class, 'deregister']);
    Route::post('heartbeat', [DaemonController::class, 'heartbeat']);
    Route::get('workspaces/{workspaceId}/repos', [DaemonController::class, 'workspaceRepos']);
    Route::post('runtimes/{runtimeId}/tasks/claim', [DaemonController::class, 'claimTask']);
    Route::post('tasks/{taskId}/start', [DaemonController::class, 'startTask']);
    Route::post('tasks/{taskId}/output', [DaemonController::class, 'outputTask']);
    Route::post('tasks/{taskId}/complete', [DaemonController::class, 'completeTask']);
    Route::post('tasks/{taskId}/fail', [DaemonController::class, 'failTask']);
    Route::post('tasks/{taskId}/cancel', [DaemonController::class, 'cancelTask']);
});
```

These routes must be stateless (no CSRF, no session). Ensure `api.php` has no session/CSRF middleware applied to these routes.

---

## Step 5 — DaemonController

Create `app/Http/Controllers/Api/DaemonController.php`.

All responses are JSON. All methods verify the authenticated user owns the referenced runtime/task.

### `register` — POST /api/daemon/register

Request body:
```json
{
  "workspace_id": "string",
  "daemon_id": "string",
  "device_name": "string",
  "cli_version": "string",
  "launched_by": "string",
  "legacy_daemon_ids": [],
  "runtimes": [
    {
      "name": "string",
      "type": "claude",
      "version": "2.1.70",
      "status": "online",
      "profile_id": ""
    }
  ]
}
```

Action:
- Validate: `daemon_id` required, `runtimes` array not empty
- For each runtime in `runtimes` array:
  - Upsert into `agent_runtimes` matching on `(daemon_id, provider)` where provider = `type` field lowercased
  - On insert: set all fields
  - On update: update `status`, `last_seen_at` = now, `device_info`, `cli_version`, `launched_by`, `metadata`
  - Always set `user_id` = authenticated user's ID
  - `device_info` = `{device_name} · {version}` if both present, otherwise whichever is set
  - `metadata` = `{"version": version, "cli_version": cli_version, "launched_by": launched_by}` as JSON
- Build repos response from the project matching `workspace_id` (try matching project `id` first, then project `slug` if you have one, otherwise return empty repos array)
- Compute `repos_version` = `hash('sha256', implode(',', array_column($repos, 'url')))`

Response:
```json
{
  "runtimes": [
    {
      "id": "uuid",
      "name": "string",
      "provider": "claude",
      "status": "online",
      "device_info": "string"
    }
  ],
  "repos": [],
  "repos_version": "string",
  "settings": {}
}
```

### `deregister` — POST /api/daemon/deregister

Request body:
```json
{"runtime_ids": ["uuid", "uuid"]}
```

Action:
- For each runtime ID: find record, verify `user_id` = authenticated user, set `status = offline`, update `last_seen_at`
- Silently skip any ID not found or not owned by user

Response: `{"status": "ok"}`

### `heartbeat` — POST /api/daemon/heartbeat

Request body:
```json
{"runtime_id": "uuid"}
```

Action:
- Find runtime by ID, verify `user_id` = authenticated user
- If not found: return `404 {"error": "runtime not found", "runtime_gone": true}`
- Update `last_seen_at` = now, ensure `status = online`

Response:
```json
{
  "status": "ok",
  "runtime_id": "uuid"
}
```

### `workspaceRepos` — GET /api/daemon/workspaces/{workspaceId}/repos

Action:
- Find project where `id = workspaceId`
- Verify the authenticated user has access to this project (belongs to a team assigned to it, or user is manager/admin)
- Return `repos` JSON field from project (or empty array if null)
- Compute `repos_version`

Response:
```json
{
  "workspace_id": "string",
  "repos": [
    {
      "url": "string",
      "description": "string",
      "stack": "string",
      "local_path": "string"
    }
  ],
  "repos_version": "string",
  "settings": {}
}
```

### `claimTask` — POST /api/daemon/runtimes/{runtimeId}/tasks/claim

This is the core polling endpoint. Daemon calls this every 3 seconds.

Action:
- Find runtime by ID, verify `user_id` = authenticated user
- If not found: return `404 {"error": "runtime not found"}`
- Find the agent user linked to this runtime (`agent_runtimes.user_id` where that user has `is_agent = true`)
  - Note: the authenticated user (daemon owner) and the agent user may differ — the daemon owner is the developer, the agent user is the AI agent account. For now, use the authenticated user directly and check `is_agent = true`. If user is not an agent, return `{"task": null}`
- Find one record in `agent_task_queue` where:
  - `status = queued`
  - The related task's `assigned_to` = authenticated user's ID
  - Order by `created_at` ASC (oldest first)
- Use database-level locking to prevent two daemons claiming the same task: wrap in a transaction with `lockForUpdate()`
- If found:
  - Update queue `status = dispatched`, `runtime_id` = this runtime's ID, `claimed_at` = now
  - Return task details
- If not found: return `{"task": null}`

Response when task found:
```json
{
  "task": {
    "id": "queue-uuid",
    "task_id": "task-id",
    "issue_id": "task-{task_id}",
    "title": "task title",
    "description": "full prompt from buildPrompt()",
    "workspace_id": "project-id",
    "local_directory": "matched local_path from project repos based on runtime provider/stack"
  }
}
```

`local_directory` logic: look at the project's `repos` JSON, find the entry where `stack` matches `runtime.provider` (e.g. runtime provider = `claude` with stack = `laravel` → find repo with `stack = laravel`). If no match found, return first repo's `local_path`, or empty string.

Response when no task:
```json
{"task": null}
```

### `startTask` — POST /api/daemon/tasks/{taskId}/start

`taskId` here is the `agent_task_queue.id` (UUID), not `tasks.id`.

Action:
- Find queue entry by ID, verify the related task's `assigned_to` = authenticated user
- Update queue `status = running`, `started_at` = now
- Update related `tasks.status` = `in-progress` (use the kanban column slug `in-progress`)

Response: `{"status": "ok"}`

### `outputTask` — POST /api/daemon/tasks/{taskId}/output

Request body:
```json
{
  "content": "string",
  "type": "text",
  "tool": "string",
  "seq": 1
}
```

Action:
- Find queue entry, verify ownership
- Append content to `agent_task_queue.output` (concatenate with newline separator)
- If `type = text` and `content` is not empty: create a `TaskComment` on the related task with:
  - `user_id` = authenticated user (agent user)
  - `body` = content
- Do NOT create a comment for every chunk if content is very short (less than 10 chars) — batch output comments should only be posted for meaningful content

Response: `{"status": "ok"}`

### `completeTask` — POST /api/daemon/tasks/{taskId}/complete

Request body:
```json
{
  "output": "string",
  "pr_url": "string"
}
```

Action:
- Find queue entry, verify ownership
- Update queue `status = completed`, `completed_at` = now, store `pr_url` if present
- Update related `tasks.status` = `in-review`
- Post a `TaskComment`: body = `"✅ Agent completed this task." + (output summary if present)`

Response: `{"status": "ok"}`

### `failTask` — POST /api/daemon/tasks/{taskId}/fail

Request body:
```json
{"error": "string"}
```

Action:
- Find queue entry, verify ownership
- Update queue `status = failed`, `error_message` = error
- Update related `tasks.status` = `open`
- Post a `TaskComment`: body = `"❌ Agent failed: {error}"`

Response: `{"status": "ok"}`

### `cancelTask` — POST /api/daemon/tasks/{taskId}/cancel

Action:
- Find queue entry, verify ownership
- Update queue `status = cancelled`
- Post a `TaskComment`: body = `"⚠️ Task cancelled."`

Response: `{"status": "ok"}`

---

## Step 6 — Task Queue Population

In `TaskController`, after saving a task (both `store()` and `update()`):

Check if `assigned_to` changed to a non-null user who has `is_agent = true`. If yes:
- Check no existing `agent_task_queue` record exists for this task with status in `[queued, dispatched, running]`
- If none: create new `AgentTaskQueue` record with:
  - `task_id` = task ID
  - `status = queued`
  - `prompt` = `AgentTaskQueue::buildPrompt($task)`

Also do the same in `TaskController::claim()` — when a user claims a task and they have `is_agent = true`.

---

## Step 7 — Runtime Sweeper

Create Artisan command `app/Console/Commands/DaemonSweep.php`:
- Command signature: `daemon:sweep`
- Finds all `agent_runtimes` where `status = online` AND `last_seen_at < now - 3 minutes`
- Sets them to `status = offline`
- Logs count of runtimes swept

Register in scheduler (in `routes/console.php` or `Kernel.php`):
```php
Schedule::command('daemon:sweep')->everyTwoMinutes();
```

---

## Step 8 — Token Management UI (User Profile Page)

Add a "Daemon Tokens" section to the existing user profile/edit page (`resources/views/users/form.blade.php` or wherever the profile is — find the right view).

### Display
Show a table of existing daemon tokens for the current user:
- Columns: Name, Created, Last Used, Actions
- Last Used shows "Never" if null, otherwise human-readable date
- Actions: "Revoke" button per token

### Generate new token
A form with:
- Text input: "Token name" (e.g. "MacBook Pro")
- Button: "Generate Token"

On submit (`POST /profile/daemon-tokens`):
1. Generate raw token using `DaemonTokenService::generate()`
2. Store hashed token in `daemon_tokens` with name and user_id
3. Redirect back with the raw token flashed to session ONCE
4. Show the raw token in a highlighted copy-to-clipboard box with warning: "Copy this token now — it will not be shown again."

### Revoke token
`DELETE /profile/daemon-tokens/{id}`:
1. Verify token belongs to authenticated user
2. Delete from DB
3. Invalidate Laravel cache key `daemon_token:{hash}` immediately
4. Redirect back with success message

### Routes (add to `routes/web.php`):
```php
Route::post('/profile/daemon-tokens', [DaemonTokenController::class, 'store'])->name('daemon-tokens.store');
Route::delete('/profile/daemon-tokens/{token}', [DaemonTokenController::class, 'destroy'])->name('daemon-tokens.destroy');
```

Create `app/Http/Controllers/DaemonTokenController.php` with `store()` and `destroy()` methods.

---

## Step 9 — Artisan Token Creation Command

Create `app/Console/Commands/DaemonTokenCreate.php`:
- Signature: `daemon:token:create {user_id} {name}`
- Generates token, stores hash, prints raw token once to console
- Useful for onboarding developers without going through the UI

---

## Do Not Touch
- No existing routes, controllers, views, or Blade templates except:
  - Adding the "Daemon Tokens" section to the user profile view
  - Adding 2 lines to `TaskController::store()` and `TaskController::update()` for queue population
- No changes to existing kanban, sprint, backlog, invoice, expense, or AI chat panel logic
- No Inertia, no Livewire in the new daemon API routes — pure JSON only
- The `DaemonTokenMiddleware` must only apply to `/api/daemon/*` routes — never to web routes
- Do not add CSRF middleware to the daemon API routes