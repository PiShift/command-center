<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePanelAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->hasPermission('roles.view')) {
            return redirect('/war-room');
        }

        return $next($request);
    }
}
