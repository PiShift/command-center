<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\ProjectTeamController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\BacklogItemController;
use App\Http\Controllers\TaskChecklistController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MonthlyBudgetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InvoiceReminderController;
use App\Http\Controllers\SettingsNotificationController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Multi-step verification
Route::get('/login/verify',  [LoginController::class, 'showVerify'])->name('login.verify');
Route::post('/login/verify', [LoginController::class, 'verify'])->name('login.verify.submit');
Route::post('/login/resend-otp', [LoginController::class, 'resendOtp'])->name('login.resend-otp');

// ── Authenticated app ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'require-2fa'])->group(function () {

    Route::get('/', function () {
        $role = auth()->user()?->roleModel?->slug;
        return $role === 'developer'
            ? redirect()->route('board')
            : redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Board (Kanban) — Livewire component mounted in a Blade view
    Route::get('/board', fn () => view('board.index'))->name('board');

    // Profile
    Route::get('/profile',               [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile',             [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password',    [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');
    Route::delete('/profile/devices/{device}', [ProfileController::class, 'revokeDevice'])->name('profile.devices.revoke');
    Route::delete('/profile/devices',    [ProfileController::class, 'revokeAllDevices'])->name('profile.devices.revoke-all');

    // Two-Factor Authentication
    Route::get('/profile/2fa/setup',              [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/profile/2fa/enable',            [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/profile/2fa/disable',           [TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::post('/profile/2fa/regenerate-codes',  [TwoFactorController::class, 'regenerateCodes'])->name('2fa.regenerate-codes');
    Route::get('/profile/2fa/debug',              [TwoFactorController::class, 'debug'])->name('2fa.debug');

    // Projects
    Route::resource('projects', ProjectController::class)->names('projects');
    Route::post('projects/{project}/teams/sync', [ProjectController::class, 'assignTeams'])->name('projects.assign-teams');

    // Sprints (scoped under project)
    Route::post('projects/{project}/sprints', [SprintController::class, 'store'])->name('sprints.store');
    Route::patch('projects/{project}/sprints/{sprint}', [SprintController::class, 'update'])->name('sprints.update');
    Route::delete('projects/{project}/sprints/{sprint}', [SprintController::class, 'destroy'])->name('sprints.destroy');
    Route::post('projects/{project}/sprints/{sprint}/publish', [SprintController::class, 'publish'])->name('sprints.publish');
    Route::post('projects/{project}/sprints/{sprint}/unpublish', [SprintController::class, 'unpublish'])->name('sprints.unpublish');
    Route::post('projects/{project}/sprints/{sprint}/complete', [SprintController::class, 'complete'])->name('sprints.complete');

    // Backlog items (scoped under project)
    Route::post('projects/{project}/backlog', [BacklogItemController::class, 'store'])->name('backlog.store');
    // Bulk routes must come before the {backlogItem} wildcard routes
    Route::patch('projects/{project}/backlog/bulk-sprint', [BacklogItemController::class, 'bulkSprint'])->name('backlog.bulk-sprint');
    Route::post('projects/{project}/backlog/bulk-promote', [BacklogItemController::class, 'bulkPromote'])->name('backlog.bulk-promote');
    Route::delete('projects/{project}/backlog/bulk-delete', [BacklogItemController::class, 'bulkDelete'])->name('backlog.bulk-delete');
    Route::patch('projects/{project}/backlog/{backlogItem}', [BacklogItemController::class, 'update'])->name('backlog.update');
    Route::delete('projects/{project}/backlog/{backlogItem}', [BacklogItemController::class, 'destroy'])->name('backlog.destroy');
    Route::post('projects/{project}/backlog/{backlogItem}/promote', [BacklogItemController::class, 'promote'])->name('backlog.promote');

    // Tasks
    Route::resource('tasks', TaskController::class)->names('tasks');
    Route::patch('/tasks/{task}/advance', [TaskController::class, 'advance'])->name('tasks.advance');
    Route::post('/tasks/{task}/claim', [TaskController::class, 'claim'])->name('tasks.claim');
    // Checklists
    Route::post('/tasks/{task}/checklists', [TaskChecklistController::class, 'store'])->name('checklists.store');
    Route::patch('/tasks/{task}/checklists/{item}', [TaskChecklistController::class, 'update'])->name('checklists.update');
    Route::delete('/tasks/{task}/checklists/{item}', [TaskChecklistController::class, 'destroy'])->name('checklists.destroy');

    // Customers
    Route::resource('customers', CustomerController::class)->names('customers');

    // Users
    Route::resource('users', UserController::class)->names('users')->parameters(['users' => 'user']);

    // Roles
    Route::resource('roles', RoleController::class)->names('roles');

    // Invoices
    Route::resource('invoices', InvoiceController::class)->names('invoices');
    Route::post('invoices/{invoice}/publish',       [InvoiceController::class, 'publish'])->name('invoices.publish');
    Route::post('invoices/{invoice}/resend',        [InvoiceController::class, 'resend'])->name('invoices.resend');
    Route::patch('invoices/{invoice}/cancel',       [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::patch('invoices/{invoice}/reset-draft',  [InvoiceController::class, 'resetToDraft'])->name('invoices.reset-draft');
    Route::get('invoices/{invoice}/preview',        [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('invoices/{invoice}/download',       [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('invoices/bulk-action',             [InvoiceController::class, 'bulkAction'])->name('invoices.bulk-action');

    // Invoice payments
    Route::post('invoices/{invoice}/payments',      [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
    Route::get('payments',                          [PaymentController::class, 'index'])->name('payments.index');

    // Credits
    Route::get('customers/{customer}/credits',      [CreditController::class, 'index'])->name('credits.index');
    Route::post('invoices/{invoice}/apply-credit',  [CreditController::class, 'apply'])->name('invoices.apply-credit');

    // Teams
    Route::resource('teams', TeamController::class)->names('teams');
    Route::post('teams/{team}/members',             [TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::delete('teams/{team}/members/{user}',    [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
    Route::post('projects/{project}/teams',         [ProjectTeamController::class, 'store'])->name('projects.teams.store');
    Route::delete('projects/{project}/teams/{team}',[ProjectTeamController::class, 'destroy'])->name('projects.teams.destroy');

    // AI
    Route::post('projects/{project}/ai/plan',         [AiController::class, 'plan'])->name('ai.plan');
    Route::post('projects/{project}/ai/plan/confirm', [AiController::class, 'confirmPlan'])->name('ai.plan.confirm');
    Route::post('projects/{project}/ai/promote-suggestions', [AiController::class, 'promoteSuggestions'])->name('ai.promote-suggestions');
    Route::post('tasks/{task}/ai/generate-guide',     [AiController::class, 'generateGuide'])->name('ai.generate-guide');

    // Attachments
    Route::post('tasks/{task}/attachments',                     [TaskAttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('tasks/{task}/attachments/{media}',           [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('tasks/{task}/attachments/{media}/download',     [TaskAttachmentController::class, 'download'])->name('attachments.download');

    // Comment attachments
    Route::post('tasks/{task}/comments/{comment}/attachment',    [TaskCommentAttachmentController::class, 'store'])->name('comment-attachments.store');
    Route::delete('tasks/{task}/comments/{comment}/attachment',  [TaskCommentAttachmentController::class, 'destroy'])->name('comment-attachments.destroy');
    Route::get('tasks/{task}/comments/{comment}/attachment',     [TaskCommentAttachmentController::class, 'download'])->name('comment-attachments.download');

    // Comments (show page + modal AJAX)
    Route::post('tasks/{task}/comments',           [TaskCommentController::class, 'store'])->name('task-comments.store');
    Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('task-comments.destroy');

    // Expense Management
    Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/recurring-charges/{recurringCharge}/toggle', [ExpenseController::class, 'toggleRecurring'])->name('recurring-charges.toggle');
    // Named routes with conflicts must come before resource (monthlyOverview, bulk, generateDrafts)
    Route::get('/expenses/monthly-overview',    [ExpenseController::class, 'monthlyOverview'])->name('expenses.monthly-overview');
    Route::post('/expenses/bulk-confirm',       [ExpenseController::class, 'bulkConfirm'])->name('expenses.bulk-confirm');
    Route::post('/expenses/generate-drafts',    [ExpenseController::class, 'generateDrafts'])->name('expenses.generate-drafts');
    Route::resource('expenses', ExpenseController::class);
    Route::patch('/expenses/{expense}/confirm', [ExpenseController::class, 'confirm'])->name('expenses.confirm');

    // Monthly Budgets
    Route::get('/monthly-budgets',              [MonthlyBudgetController::class, 'index'])->name('monthly-budgets.index');
    Route::post('/monthly-budgets',             [MonthlyBudgetController::class, 'store'])->name('monthly-budgets.store');
    Route::delete('/monthly-budgets/{budget}',  [MonthlyBudgetController::class, 'destroy'])->name('monthly-budgets.destroy');

    // Invoice reminders
    Route::post('/invoices/{invoice}/reminders',              [InvoiceReminderController::class, 'store'])->name('invoices.reminders.store');
    Route::delete('/invoices/{invoice}/reminders/{reminder}', [InvoiceReminderController::class, 'destroy'])->name('invoices.reminders.destroy');

    // In-app notifications
    Route::get('/notifications',             [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',   [NotificationController::class, 'readAll'])->name('notifications.read-all');

    // Admin notification settings
    Route::get('/settings/notifications',              [SettingsNotificationController::class, 'show'])->name('settings.notifications');
    Route::patch('/settings/notifications',            [SettingsNotificationController::class, 'update'])->name('settings.notifications.update');
    Route::post('/settings/notifications/test-slack',  [SettingsNotificationController::class, 'testSlack'])->name('settings.notifications.test-slack');
});
