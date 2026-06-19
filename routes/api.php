<?php

use App\Http\Controllers\Api\DaemonController;
use App\Http\Controllers\Api\AuthCodeController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\WorkspaceApiController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Middleware\ApiSessionOrPatAuth;
use App\Http\Middleware\DaemonTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('api.secret')->prefix('v1')->group(function () {
    Route::get('customers/{identifier}/invoices', [CustomerApiController::class, 'invoices']);
    Route::post('customers/invoices', [CustomerApiController::class, 'invoices']);
});

Route::post('/auth/send-code', [AuthCodeController::class, 'sendCode']);
Route::post('/auth/verify-code', [AuthCodeController::class, 'verifyCode']);

Route::middleware([
    \Illuminate\Session\Middleware\StartSession::class,
    ApiSessionOrPatAuth::class,
])->group(function () {
    Route::post('/tokens', [PersonalAccessTokenController::class, 'store']);
    Route::get('/tokens', [PersonalAccessTokenController::class, 'index']);
    Route::delete('/tokens/{id}', [PersonalAccessTokenController::class, 'destroy']);
    Route::get('/me', [MeController::class, 'show']);
    Route::get('/workspaces', [WorkspaceApiController::class, 'index']);
});

Route::prefix('daemon')->middleware(DaemonTokenMiddleware::class)->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::get('workspaces/{workspaceId}', [WorkspaceController::class, 'show']);

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
