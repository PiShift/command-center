<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentSkillController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AiConversationController;
use App\Http\Controllers\Api\AuthCodeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BacklogItemController;
use App\Http\Controllers\BankAccountTransferController;
use App\Http\Controllers\ChecklistTemplateController;
use App\Http\Controllers\CompanyBankAccountController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DaemonTokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeAdvanceController;
use App\Http\Controllers\EmployeeBankAccountController;
use App\Http\Controllers\EmployeeContractController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeLeaveController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\InvoiceReminderController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\MonthlyBudgetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ProjectTeamController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RuntimeController;
use App\Http\Controllers\SettingsNotificationController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskChecklistController;
use App\Http\Controllers\TaskCommentAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskComponentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskInvoiceOverrideController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Multi-step verification
Route::get('/login/verify', [LoginController::class, 'showVerify'])->name('login.verify');
Route::post('/login/verify', [LoginController::class, 'verify'])->name('login.verify.submit');
Route::post('/login/resend-otp', [LoginController::class, 'resendOtp'])->name('login.resend-otp');

Route::post('/auth/send-code', [AuthCodeController::class, 'sendCode']);
Route::post('/auth/verify-code', [AuthCodeController::class, 'verifyCode']);
Route::post('/auth/logout', function () {
    Auth::logout();

    return response()->json(['status' => 'ok']);
});

Route::get('/.well-known/oauth-authorization-server', [OAuthController::class, 'authorizationServer'])
    ->name('oauth.authorization-server');
Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');
Route::post('/oauth/authorize', [OAuthController::class, 'handleAuthorize'])->name('oauth.authorize.handle');
Route::post('/oauth/token', [OAuthController::class, 'token'])->name('oauth.token');

// ── Authenticated app ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'require-2fa'])->group(function () {

    Route::get('/', function () {
        $role = auth()->user()?->roleModel?->slug;

        return $role === 'developer'
            ? redirect()->route('board')
            : redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global Search
    Route::get('/search', [GlobalSearchController::class, 'search'])->name('search');

    // Board (Kanban) — Livewire component mounted in a Blade view
    Route::get('/board', fn () => view('board.index'))->name('board');

    // Agents
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
    Route::post('/agents/{agent}/archive', [AgentController::class, 'archive'])->name('agents.archive');
    Route::post('/agents/{agent}/restore', [AgentController::class, 'restore'])->name('agents.restore');

    // Skills
    Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
    Route::get('/skills/create', [SkillController::class, 'create'])->name('skills.create');
    Route::post('/skills', [SkillController::class, 'store'])->name('skills.store');
    Route::get('/skills/{skill}', [SkillController::class, 'show'])->name('skills.show');
    Route::put('/skills/{skill}', [SkillController::class, 'update'])->name('skills.update');
    Route::delete('/skills/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');
    Route::post('/agents/{agent}/skills', [AgentSkillController::class, 'attach'])->name('agent-skills.attach');
    Route::delete('/agents/{agent}/skills/{skill}', [AgentSkillController::class, 'detach'])->name('agent-skills.detach');

    // Runtimes
    Route::get('/runtimes', [RuntimeController::class, 'index'])->name('runtimes.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');
    Route::delete('/profile/devices/{device}', [ProfileController::class, 'revokeDevice'])->name('profile.devices.revoke');
    Route::delete('/profile/devices', [ProfileController::class, 'revokeAllDevices'])->name('profile.devices.revoke-all');
    Route::post('/profile/daemon-tokens', [DaemonTokenController::class, 'store'])->name('daemon-tokens.store');
    Route::delete('/profile/daemon-tokens/{token}', [DaemonTokenController::class, 'destroy'])->name('daemon-tokens.destroy');

    // Two-Factor Authentication
    Route::get('/profile/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/profile/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/profile/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::post('/profile/2fa/regenerate-codes', [TwoFactorController::class, 'regenerateCodes'])->name('2fa.regenerate-codes');
    Route::get('/profile/2fa/debug', [TwoFactorController::class, 'debug'])->name('2fa.debug');

    // Projects
    Route::resource('projects', ProjectController::class)->names('projects');
    Route::post('projects/{project}/teams/sync', [ProjectController::class, 'assignTeams'])->name('projects.assign-teams');

    // Project documents
    Route::get('projects/{project}/documents', [ProjectDocumentController::class, 'index'])->name('projects.documents.index');
    Route::post('projects/{project}/documents', [ProjectDocumentController::class, 'store'])->name('projects.documents.store');
    Route::patch('projects/{project}/documents/{doc}', [ProjectDocumentController::class, 'update'])->name('projects.documents.update');
    Route::delete('projects/{project}/documents/{doc}', [ProjectDocumentController::class, 'destroy'])->name('projects.documents.destroy');

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

    // Checklist templates (Definition of Done baselines — manager/admin only)
    Route::middleware('permission:projects.manage')->group(function () {
        Route::resource('checklist-templates', ChecklistTemplateController::class)
            ->names('checklist-templates')
            ->except(['show']);

        Route::get('/settings/task-components', [TaskComponentController::class, 'index'])->name('task-components.index');
        Route::post('/settings/task-components', [TaskComponentController::class, 'store'])->name('task-components.store');
        Route::patch('/settings/task-components/{taskComponent}', [TaskComponentController::class, 'update'])->name('task-components.update');
        Route::delete('/settings/task-components/{taskComponent}', [TaskComponentController::class, 'destroy'])->name('task-components.destroy');
        Route::post('/settings/task-components/bulk-reassign', [TaskComponentController::class, 'bulkReassign'])->name('task-components.bulk-reassign');
    });

    // Tasks
    Route::resource('tasks', TaskController::class)->names('tasks')->except(['create']);
    Route::patch('/tasks/{task}/advance', [TaskController::class, 'advance'])->name('tasks.advance');
    Route::post('/tasks/{task}/change-requests', [TaskController::class, 'storeChangeRequest'])->name('tasks.change-requests.store');
    Route::get('/tasks/{task}/change-requests/{changeRequest}/attachments/{media}/download', [TaskController::class, 'downloadChangeRequestAttachment'])->name('tasks.change-requests.attachments.download');
    Route::post('/tasks/{task}/claim', [TaskController::class, 'claim'])->name('tasks.claim');
    Route::post('/projects/{project}/claim-selected', [ProjectController::class, 'claimSelected'])->name('projects.claim-selected');
    Route::post('/projects/{project}/sprints/{sprint}/claim-all', [ProjectController::class, 'claimAllInSprint'])->name('projects.sprints.claim-all');
    Route::post('/tasks/{task}/invoice-override', [TaskInvoiceOverrideController::class, 'store'])->name('task-invoice-overrides.store');
    Route::delete('/tasks/{task}/invoice-override', [TaskInvoiceOverrideController::class, 'destroy'])->name('task-invoice-overrides.destroy');
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
    Route::post('invoices/{invoice}/publish', [InvoiceController::class, 'publish'])->name('invoices.publish');
    Route::post('invoices/{invoice}/resend', [InvoiceController::class, 'resend'])->name('invoices.resend');
    Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::patch('invoices/{invoice}/reset-draft', [InvoiceController::class, 'resetToDraft'])->name('invoices.reset-draft');
    Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('invoices/bulk-action', [InvoiceController::class, 'bulkAction'])->name('invoices.bulk-action');

    // Invoice payments
    Route::post('invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

    // Credits
    Route::get('customers/{customer}/credits', [CreditController::class, 'index'])->name('credits.index');
    Route::post('invoices/{invoice}/apply-credit', [CreditController::class, 'apply'])->name('invoices.apply-credit');

    // Teams
    Route::resource('teams', TeamController::class)->names('teams');
    Route::post('teams/{team}/members', [TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::delete('teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
    Route::post('projects/{project}/teams', [ProjectTeamController::class, 'store'])->name('projects.teams.store');
    Route::delete('projects/{project}/teams/{team}', [ProjectTeamController::class, 'destroy'])->name('projects.teams.destroy');

    Route::get('/workspaces/new', function () {
        return redirect('/teams/create');
    });

    // AI
    Route::post('projects/{project}/ai/plan', [AiController::class, 'plan'])->name('ai.plan');
    Route::post('projects/{project}/ai/plan/confirm', [AiController::class, 'confirmPlan'])->name('ai.plan.confirm');
    Route::post('projects/{project}/ai/promote-suggestions', [AiController::class, 'promoteSuggestions'])->name('ai.promote-suggestions');
    Route::post('tasks/{task}/ai/generate-guide', [AiController::class, 'generateGuide'])->name('ai.generate-guide');
    Route::post('ai/conversation/stream', [AiConversationController::class, 'stream'])->name('ai.conversation.stream')->middleware('throttle:30,1');

    // Attachments
    Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('tasks/{task}/attachments/{media}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('tasks/{task}/attachments/{media}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');

    // Comment attachments
    Route::post('tasks/{task}/comments/{comment}/attachment', [TaskCommentAttachmentController::class, 'store'])->name('comment-attachments.store');
    Route::delete('tasks/{task}/comments/{comment}/attachment', [TaskCommentAttachmentController::class, 'destroy'])->name('comment-attachments.destroy');
    Route::get('tasks/{task}/comments/{comment}/attachment', [TaskCommentAttachmentController::class, 'download'])->name('comment-attachments.download');

    // Comments (show page + modal AJAX)
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('task-comments.store');
    Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('task-comments.destroy');

    // Expense Management
    Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/recurring-charges/{recurringCharge}/toggle', [ExpenseController::class, 'toggleRecurring'])->name('recurring-charges.toggle');
    // Named routes with conflicts must come before resource (monthlyOverview, bulk, generateDrafts)
    Route::get('/expenses/monthly-overview', [ExpenseController::class, 'monthlyOverview'])->name('expenses.monthly-overview');
    Route::post('/expenses/bulk-confirm', [ExpenseController::class, 'bulkConfirm'])->name('expenses.bulk-confirm');
    Route::post('/expenses/generate-drafts', [ExpenseController::class, 'generateDrafts'])->name('expenses.generate-drafts');
    Route::resource('expenses', ExpenseController::class);
    Route::patch('/expenses/{expense}/confirm', [ExpenseController::class, 'confirm'])->name('expenses.confirm');

    // Company Bank Accounts (Finance)
    Route::middleware('permission:finance.manage')->group(function () {
        Route::get('/bank-accounts', [CompanyBankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::get('/bank-accounts/create', [CompanyBankAccountController::class, 'create'])->name('bank-accounts.create');
        Route::post('/bank-accounts', [CompanyBankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::post('/bank-accounts/transfer', [BankAccountTransferController::class, 'store'])->name('bank-accounts.transfer.store');
        Route::delete('/bank-accounts/transfer/{transfer}', [BankAccountTransferController::class, 'destroy'])->name('bank-accounts.transfer.destroy');
        Route::patch('/bank-accounts/{account}/usd-exchange-rate', [CompanyBankAccountController::class, 'updateUsdExchangeRate'])->name('bank-accounts.usd-rate.update');
        Route::get('/bank-accounts/{account}', [CompanyBankAccountController::class, 'show'])->name('bank-accounts.show');
        Route::get('/bank-accounts/{account}/edit', [CompanyBankAccountController::class, 'edit'])->name('bank-accounts.edit');
        Route::put('/bank-accounts/{account}', [CompanyBankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('/bank-accounts/{account}', [CompanyBankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
    });

    // Monthly Budgets
    Route::get('/monthly-budgets', [MonthlyBudgetController::class, 'index'])->name('monthly-budgets.index');
    Route::post('/monthly-budgets', [MonthlyBudgetController::class, 'store'])->name('monthly-budgets.store');
    Route::delete('/monthly-budgets/{budget}', [MonthlyBudgetController::class, 'destroy'])->name('monthly-budgets.destroy');

    // Invoice reminders
    Route::post('/invoices/{invoice}/reminders', [InvoiceReminderController::class, 'store'])->name('invoices.reminders.store');
    Route::delete('/invoices/{invoice}/reminders/{reminder}', [InvoiceReminderController::class, 'destroy'])->name('invoices.reminders.destroy');

    // In-app notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    // Admin notification settings
    Route::get('/settings/notifications', [SettingsNotificationController::class, 'show'])->name('settings.notifications');
    Route::patch('/settings/notifications', [SettingsNotificationController::class, 'update'])->name('settings.notifications.update');
    Route::post('/settings/notifications/test-slack', [SettingsNotificationController::class, 'testSlack'])->name('settings.notifications.test-slack');

    // ── HR Module ─────────────────────────────────────────────────────────────
    Route::resource('employees', EmployeeController::class)->names('employees');
    Route::post('employees/{employee}/avatar', [EmployeeController::class, 'uploadAvatar'])->name('employees.avatar');
    Route::get('employees/{employee}/contracts/create', [EmployeeContractController::class, 'create'])->name('employees.contracts.create');
    Route::post('employees/{employee}/contracts', [EmployeeContractController::class, 'store'])->name('employees.contracts.store');
    Route::get('employees/{employee}/contracts/{contract}/edit', [EmployeeContractController::class, 'edit'])->name('employees.contracts.edit');
    Route::patch('employees/{employee}/contracts/{contract}', [EmployeeContractController::class, 'update'])->name('employees.contracts.update');
    Route::get('employees/{employee}/contracts/{contract}/download', [EmployeeContractController::class, 'download'])->name('employees.contracts.download');
    Route::post('employees/{employee}/contracts/{contract}/activate', [EmployeeContractController::class, 'activate'])->name('employees.contracts.activate');
    Route::post('employees/{employee}/contracts/{contract}/upload-signed', [EmployeeContractController::class, 'uploadSigned'])->name('employees.contracts.upload-signed');
    Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
    Route::delete('employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employees.documents.destroy');
    Route::post('employees/{employee}/leaves', [EmployeeLeaveController::class, 'store'])->name('employees.leaves.store');
    Route::delete('employees/{employee}/leaves/{leaveRequest}', [EmployeeLeaveController::class, 'cancel'])->name('employees.leaves.cancel');

    Route::middleware('permission:hr.view')->group(function () {
        Route::get('/hr/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::get('/hr/leaves/calendar', [LeaveController::class, 'calendar'])->name('leaves.calendar');
        Route::get('/hr/leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');
    });

    Route::middleware('permission:hr.manage')->group(function () {
        Route::post('/hr/leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
        Route::put('/hr/leave-types/{type}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
        Route::delete('/hr/leave-types/{type}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
        Route::post('/hr/leaves/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/hr/leaves/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
        Route::patch('/hr/leaves/{leaveRequest}/actual-days', [LeaveController::class, 'updateActualDays'])->name('leaves.actual-days.update');
        Route::post('/hr/leaves/bulk-approve', [LeaveController::class, 'bulkApprove'])->name('leaves.bulk-approve');
        Route::post('/hr/leaves/bulk-reject', [LeaveController::class, 'bulkReject'])->name('leaves.bulk-reject');

        Route::post('employees/{employee}/bank-accounts', [EmployeeBankAccountController::class, 'store'])->name('employees.bank-accounts.store');
        Route::put('employees/{employee}/bank-accounts/{account}', [EmployeeBankAccountController::class, 'update'])->name('employees.bank-accounts.update');
        Route::delete('employees/{employee}/bank-accounts/{account}', [EmployeeBankAccountController::class, 'destroy'])->name('employees.bank-accounts.destroy');
        Route::post('employees/{employee}/bank-accounts/{account}/set-primary', [EmployeeBankAccountController::class, 'setPrimary'])->name('employees.bank-accounts.set-primary');

        Route::post('employees/{employee}/advances', [EmployeeAdvanceController::class, 'store'])->name('employees.advances.store');
        Route::patch('employees/{employee}/advances/{advance}', [EmployeeAdvanceController::class, 'update'])->name('employees.advances.update');
        Route::delete('employees/{employee}/advances/{advance}', [EmployeeAdvanceController::class, 'destroy'])->name('employees.advances.destroy');

        Route::post('employees/{employee}/loans', [EmployeeLoanController::class, 'store'])->name('employees.loans.store');
        Route::get('employees/{employee}/loans/{loan}', [EmployeeLoanController::class, 'show'])->name('employees.loans.show');
        Route::patch('employees/{employee}/loans/{loan}', [EmployeeLoanController::class, 'update'])->name('employees.loans.update');
        Route::delete('employees/{employee}/loans/{loan}', [EmployeeLoanController::class, 'destroy'])->name('employees.loans.destroy');
        Route::post('employees/{employee}/loans/{loan}/cancel', [EmployeeLoanController::class, 'cancel'])->name('employees.loans.cancel');

        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{run}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::post('/payroll/{run}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::post('/payroll/{run}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
        Route::get('/payroll/{run}/pdf', [PayrollController::class, 'pdf'])->name('payroll.pdf');
        Route::delete('/payroll/{run}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        Route::patch('/payroll/{run}/entries/{entry}', [PayrollController::class, 'updateEntry'])->name('payroll.entries.update');
    });

    // Contract Templates
    Route::get('hr/contract-templates', [ContractTemplateController::class, 'index'])->name('contract-templates.index');
    Route::get('hr/contract-templates/create', [ContractTemplateController::class, 'create'])->name('contract-templates.create');
    Route::post('hr/contract-templates', [ContractTemplateController::class, 'store'])->name('contract-templates.store');
    Route::get('hr/contract-templates/{contractTemplate}/edit', [ContractTemplateController::class, 'edit'])->name('contract-templates.edit');
    Route::put('hr/contract-templates/{contractTemplate}', [ContractTemplateController::class, 'update'])->name('contract-templates.update');
    Route::delete('hr/contract-templates/{contractTemplate}', [ContractTemplateController::class, 'destroy'])->name('contract-templates.destroy');
    Route::post('hr/contract-templates/{contractTemplate}/preview', [ContractTemplateController::class, 'preview'])->name('contract-templates.preview');
});
