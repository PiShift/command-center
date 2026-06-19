<?php

use App\Http\Controllers\Api\DaemonController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Middleware\DaemonTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('api.secret')->prefix('v1')->group(function () {
    Route::get('customers/{identifier}/invoices', [CustomerApiController::class, 'invoices']);
    Route::post('customers/invoices', [CustomerApiController::class, 'invoices']);
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
