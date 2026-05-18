<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireWarRoomAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission('tasks.view')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
