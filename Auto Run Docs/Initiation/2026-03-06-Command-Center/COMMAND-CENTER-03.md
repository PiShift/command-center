# Phase 03: Filament CRUD Resources

This phase wires the database layer into a fully functional Filament admin panel — with complete CRUD for Customers, Projects, and Tasks. Every resource includes a searchable table with relevant columns, a clean form for creating and editing records, and filters that make navigation fast. By the end, the developer can manage the full lifecycle of a client project — from customer onboarding to task tracking — entirely through the UI with zero code changes needed.

## Tasks

- [x] Create the `CustomerResource` with full table and form:
  - Generate with: `php artisan make:filament-resource Customer --generate`
  - **Table columns**: `name` (searchable), `company` (searchable), `email`, `projects_count` (using `withCount`), `created_at` (since, toggleable)
  - **Table filters**: none needed at this stage
  - **Table actions**: Edit, Delete (with confirmation)
  - **Table bulk actions**: Delete selected
  - **Form fields**: `name` (TextInput, required), `company` (TextInput, nullable), `email` (TextInput, email type, nullable), `notes` (Textarea, nullable, 3 rows)
  - **Navigation**: group `People`, icon `heroicon-o-users`, sort order 20
  - Add `->withCount('projects')` to the Eloquent query in `table()` via `->modifyQueryUsing()`

- [x] Create the `ProjectResource` with full table, form, and relationship panel:
  - Generate with: `php artisan make:filament-resource Project --generate`
  - **Table columns**: `name` (searchable), `customer.name` (relationship column, label "Customer"), `status` (badge — green for active, yellow for paused, grey for complete), `stack`, `tasks_count` (using `withCount`), `github_repo` (copyable, toggleable), `updated_at` (since, toggleable)
  - **Table filters**: SelectFilter on `status` (active/paused/complete), SelectFilter on `customer_id` (label "Customer", options from Customer model)
  - **Table actions**: Edit, Delete (with confirmation)
  - **Form fields**:
    - `customer_id` (Select, searchable, relationship to Customer by name, nullable)
    - `name` (TextInput, required)
    - `description` (Textarea, nullable, 3 rows)
    - `github_repo` (TextInput, nullable, placeholder `org/repo-name`, hint "e.g. pishift/acme-platform")
    - `stack` (TextInput, nullable, placeholder "e.g. Laravel + Vue, Next.js + Supabase")
    - `status` (Select, options: active/paused/complete, default active)
  - **Navigation**: group `Projects`, icon `heroicon-o-folder-open`, sort order 1
  - Add `->withCount('tasks')` to the Eloquent query

- [x] Create the `TaskResource` with full table, form, and scoped project view:
  - Generate with: `php artisan make:filament-resource Task --generate`
  - **Table columns**: `title` (searchable, wrap), `project.name` (relationship column, label "Project"), `type` (badge — blue for feature, red for bug, grey for change), `priority` (badge — red for high, yellow for medium, grey for low), `status` (badge — grey for backlog, blue for in-progress, green for done), `source` (badge, toggleable), `updated_at` (since, toggleable)
  - **Table filters**: SelectFilter on `status`, SelectFilter on `type`, SelectFilter on `priority`, SelectFilter on `project_id` (label "Project", options from Project model)
  - **Table actions**: Edit, Delete (with confirmation)
  - **Form fields**:
    - `project_id` (Select, required, searchable, relationship to Project by name)
    - `title` (TextInput, required)
    - `description` (Textarea, nullable, 4 rows)
    - `type` (Select, options: bug/feature/change, default feature)
    - `priority` (Select, options: low/medium/high, default medium)
    - `status` (Select, options: backlog/in-progress/done, default backlog)
    - `source` (Select, options: manual/ai-chat, default manual — hidden in create form, visible in edit)
    - `original_input` (Textarea, nullable, label "Original Input (raw customer feedback)", visible only when source is ai-chat — use `->hidden(fn ($get) => $get('source') !== 'ai-chat')`)
  - **Navigation**: group `Projects`, icon `heroicon-o-clipboard-document-list`, sort order 2

- [x] Add a Stats Overview widget to the Filament dashboard showing live counts:
  - Create `app/Filament/Widgets/StatsOverviewWidget.php` extending `Filament\Widgets\StatsOverviewWidget`
  - Stats cards:
    - "Active Projects" — count of projects where status = active, color blue
    - "Open Tasks" — count of tasks where status != done, color amber
    - "Customers" — total customer count, color grey
    - "Tasks Done" — count of tasks where status = done, color green
  - Register the widget in `AdminPanelProvider` via `->widgets([StatsOverviewWidget::class])`
  - Set `->defaultSort('id', 'desc')` on the Tasks table so newest tasks appear first
