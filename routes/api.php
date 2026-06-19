<?php

use App\Http\Controllers\Api\DaemonController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AgentTaskController;
use App\Http\Controllers\Api\AuthCodeController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ProjectResourceController;
use App\Http\Controllers\Api\RuntimeController;
use App\Http\Controllers\Api\AgentTaskSnapshotController;
use App\Http\Controllers\Api\SquadController;
use App\Http\Controllers\Api\IssueListController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\PinController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\Api\WorkspaceApiController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Middleware\ApiSessionOrPatAuth;
use App\Http\Middleware\DaemonTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/health', function() {
    return response()->json(['status' => 'ok']);
});

Route::middleware('api.secret')->prefix('v1')->group(function () {
    Route::get('customers/{identifier}/invoices', [CustomerApiController::class, 'invoices']);
    Route::post('customers/invoices', [CustomerApiController::class, 'invoices']);
});

Route::post('/auth/send-code', [AuthCodeController::class, 'sendCode']);
Route::post('/auth/verify-code', [AuthCodeController::class, 'verifyCode']);

Route::get('/config', function() {
    return response()->json([
        'cdn_domain'             => null,
        'cdn_signed'             => false,
        'allow_signup'           => false,
        'google_client_id'       => null,
        'posthog_key'            => null,
        'posthog_host'           => null,
        'analytics_environment'  => 'production',
    ]);
});

Route::middleware([
    \Illuminate\Session\Middleware\StartSession::class,
    ApiSessionOrPatAuth::class,
])->group(function () {
    Route::post('/agents', [AgentController::class, 'store']);
    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
    Route::put('/agents/{id}', [AgentController::class, 'update']);
    Route::delete('/agents/{id}', [AgentController::class, 'destroy']);

    Route::post('/tokens', [PersonalAccessTokenController::class, 'store']);
    Route::get('/tokens', [PersonalAccessTokenController::class, 'index']);
    Route::delete('/tokens/{id}', [PersonalAccessTokenController::class, 'destroy']);
    Route::get('/me', [MeController::class, 'show']);
    Route::get('/workspaces', [WorkspaceApiController::class, 'index']);
    Route::get('/runtimes', [\App\Http\Controllers\Api\RuntimeController::class, 'index']);
    Route::get('/agent-task-snapshot', [\App\Http\Controllers\Api\AgentTaskSnapshotController::class, 'index']);
    Route::get('/squads', [\App\Http\Controllers\Api\SquadController::class, 'index']);
    Route::get('/issues/child-progress', [\App\Http\Controllers\Api\IssueListController::class, 'childProgress']);
    Route::get('/issues', [\App\Http\Controllers\Api\IssueListController::class, 'index']);
    Route::get('/inbox', [\App\Http\Controllers\Api\InboxController::class, 'index']);
    Route::get('/pins', [\App\Http\Controllers\Api\PinController::class, 'index']);
    Route::get('/chat/pending-tasks', [\App\Http\Controllers\Api\ChatController::class, 'pendingTasks']);
    Route::get('/chat/sessions', [\App\Http\Controllers\Api\ChatController::class, 'sessions']);
    Route::get('/workspaces/{workspaceId}/members', [\App\Http\Controllers\Api\WorkspaceMemberController::class, 'index']);

    Route::get('/projects', [ProjectApiController::class, 'index']);
    Route::post('/projects', [ProjectApiController::class, 'store']);
    Route::get('/projects/{id}', [ProjectApiController::class, 'show']);
    Route::put('/projects/{id}', [ProjectApiController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectApiController::class, 'destroy']);

    Route::get('/projects/{id}/resources', [ProjectResourceController::class, 'index']);
    Route::post('/projects/{id}/resources', [ProjectResourceController::class, 'store']);
    Route::put('/projects/{id}/resources/{resourceId}', [ProjectResourceController::class, 'update']);
    Route::delete('/projects/{id}/resources/{resourceId}', [ProjectResourceController::class, 'destroy']);

    Route::get('/issues/{id}', [\App\Http\Controllers\Api\IssueController::class, 'show']);
    Route::put('/issues/{id}', [\App\Http\Controllers\Api\IssueController::class, 'update']);
    Route::patch('/issues/{id}', [\App\Http\Controllers\Api\IssueController::class, 'update']);
    Route::get('/issues/{id}/comments', [\App\Http\Controllers\Api\IssueController::class, 'comments']);
    Route::post('/issues/{id}/comments', [\App\Http\Controllers\Api\IssueController::class, 'storeComment']);

    Route::get('/agent-tasks/{queueId}/messages', [AgentTaskController::class, 'messages']);

    // Placeholder for future invitation implementation
    Route::get('/invitations', function() {
        return response()->json([]);
    });
});

Route::prefix('daemon')->middleware(DaemonTokenMiddleware::class)->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::get('workspaces/{workspaceId}', [WorkspaceController::class, 'show']);

    // Placeholder for future runtime profile implementation
    Route::get('workspaces/{workspaceId}/runtime-profiles', function() {
        return response()->json(['profiles' => []]);
    });
    Route::post('tasks/{taskId}/session', function() {
        return response()->json(['status' => 'ok']);
    });
    Route::post('tasks/{taskId}/messages', [DaemonController::class, 'messages']);
    Route::post('tasks/{taskId}/usage', function() {
        return response()->json(['status' => 'ok']);
    });

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