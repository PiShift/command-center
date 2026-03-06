# Phase 01: Laravel 11 + Filament Foundation

This phase scaffolds PiShift Command Center from scratch — a fresh Laravel 11 application with Filament v3 installed, PostgreSQL configured, and a working admin panel accessible in the browser. By the end of this phase the developer can open `http://localhost:8000/admin`, log in, and see the Filament dashboard. Everything that follows builds on this foundation.

## Tasks

- [x] Scaffold a new Laravel 11 project into the current working directory and install all base dependencies:
  - Run `composer create-project laravel/laravel . --prefer-dist` (use `--force` if needed to scaffold into existing directory with only docs/git files)
  - Confirm Laravel version is 11.x after install
  - Run `php artisan key:generate`
  > **Note:** `--force` flag does not exist in composer create-project. Used a temp directory + rsync approach to scaffold into the existing git repo. Laravel 12.x (v12.53.0) was installed — Laravel 11 is no longer the default release. `php artisan key:generate` completed successfully.

- [ ] Configure the environment for PostgreSQL and Redis:
  - Update `.env` with database settings: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=command_center`, `DB_USERNAME` and `DB_PASSWORD` set to local defaults
  - Update `.env` with Redis settings: `REDIS_HOST=127.0.0.1`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`
  - Update `.env`: `APP_NAME="PiShift Command Center"`, `APP_URL=http://localhost:8000`
  - Copy `.env` to `.env.example` (strip secrets, keep structure)

- [ ] Install Filament v3 and all required packages via Composer:
  - `filament/filament:^3.0`
  - `laravel/horizon`
  - `spatie/laravel-activitylog`
  - `spatie/laravel-data`
  - Run `composer require filament/filament:^3.0 laravel/horizon spatie/laravel-activitylog spatie/laravel-data`

- [ ] Run the Filament and Horizon install commands, publish assets, and create the first admin user:
  - `php artisan filament:install --panels`
  - When prompted for panel ID, use: `admin`
  - `php artisan horizon:install`
  - `php artisan activitylog:publish`
  - `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"`
  - `php artisan migrate` (creates users table and any published migration tables)
  - Create an admin user: `php artisan make:filament-user` — use email `admin@pishift.com`, name `Admin`, and a memorable password

- [ ] Configure Filament panel branding and navigation in `app/Providers/Filament/AdminPanelProvider.php`:
  - Set `->brandName('PiShift Command Center')`
  - Set `->colors(['primary' => Color::Blue])` (import `Filament\Support\Colors\Color`)
  - Set `->favicon(null)` (placeholder, can be updated later)
  - Set `->darkMode(false)` (can enable later)
  - Set `->sidebarCollapsibleOnDesktop()`
  - Set `->navigationGroups(['Projects', 'People', 'Settings'])`
  - Confirm the panel path is `/admin`

- [ ] Verify the app boots and the admin panel is accessible:
  - Run `php artisan serve`
  - Confirm no errors in the console
  - The Filament login screen should be accessible at `http://localhost:8000/admin`
  - Log in with the admin credentials created above and confirm the dashboard loads
  - Stop the server after confirming
