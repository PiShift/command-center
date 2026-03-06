# Phase 02: Database Layer — Migrations, Models & Seeders

This phase builds the entire data layer: all database migrations, Eloquent models with relationships, and a realistic seeder that populates the database with demo data. By the end of this phase the database schema is complete and the developer can see real data flowing through the system when testing the CRUD resources built in the next phase.

## Tasks

- [x] Create migrations for all four core entities in the correct dependency order:
  - `customers` table: `id`, `name` (string), `email` (string, nullable), `company` (string, nullable), `notes` (text, nullable), `timestamps`
  - `projects` table: `id`, `customer_id` (foreignId, constrained, nullOnDelete), `name` (string), `description` (text, nullable), `github_repo` (string, nullable — format: `org/repo`), `stack` (string, nullable), `status` (enum: `active`, `paused`, `complete`, default `active`), `timestamps`
  - `tasks` table: `id`, `project_id` (foreignId, constrained, cascadeOnDelete), `title` (string), `description` (text, nullable), `type` (enum: `bug`, `feature`, `change`, default `feature`), `priority` (enum: `low`, `medium`, `high`, default `medium`), `status` (enum: `backlog`, `in-progress`, `done`, default `backlog`), `source` (enum: `manual`, `ai-chat`, default `manual`), `original_input` (text, nullable — stores raw input before AI structuring), `timestamps`
  - `conversations` table: `id`, `project_id` (foreignId, constrained, cascadeOnDelete), `user_id` (foreignId, constrained), `type` (enum: `text`, `image`, `voice`, default `text`), `messages` (json — full conversation history array), `final_tasks` (json, nullable — confirmed task objects), `status` (enum: `discussing`, `confirmed`, default `discussing`), `timestamps`
  - Run `php artisan migrate` after creating all migrations

- [x] Create Eloquent models for all entities with fillable attributes, casts, and relationships:
  - `app/Models/Customer.php`:
    - `$fillable`: name, email, company, notes
    - Relationship: `hasMany(Project::class)`
    - Relationship: `hasMany(Task::class, 'project_id')` (through projects — or leave for later)
  - `app/Models/Project.php`:
    - `$fillable`: customer_id, name, description, github_repo, stack, status
    - `$casts`: status as string (or use a StatusEnum if preferred — keep simple for now)
    - Relationship: `belongsTo(Customer::class)`
    - Relationship: `hasMany(Task::class)`
    - Relationship: `hasMany(Conversation::class)`
  - `app/Models/Task.php`:
    - `$fillable`: project_id, title, description, type, priority, status, source, original_input
    - Relationship: `belongsTo(Project::class)`
  - `app/Models/Conversation.php`:
    - `$fillable`: project_id, user_id, type, status
    - `$casts`: messages as array, final_tasks as array
    - Relationship: `belongsTo(Project::class)`
    - Relationship: `belongsTo(User::class)`

- [x] Create a DatabaseSeeder that populates realistic demo data:
  - 3 Customers: e.g. "Acme Corp", "Nova Labs", "Bright Media" with realistic emails and company names
  - 5 Projects spread across the customers, with different statuses (active/paused), github_repo slugs (e.g. `pishift/acme-platform`), and stack descriptions (e.g. "Laravel + Vue", "Next.js + Supabase")
  - 10-15 Tasks across the projects with varied types (bug/feature/change), priorities, and statuses (backlog/in-progress/done) — write titles that feel realistic (e.g. "Fix login redirect on mobile Safari", "Add CSV export to reports page")
  - Run `php artisan db:seed` after creating the seeder and confirm it completes without errors
