<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'auth'        => \App\Http\Middleware\Authenticate::class,
            'war-room'    => \App\Http\Middleware\RequireWarRoomAccess::class,
            'permission'  => \App\Http\Middleware\RequirePermission::class,
            'require-2fa' => \App\Http\Middleware\RequiresTwoFactor::class,
            'api.secret'  => \App\Http\Middleware\ApiSecretKey::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'auth/send-code',
            'auth/verify-code',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
