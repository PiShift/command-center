<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->requiresTwoFactor() && !$user->isTwoFactorEnabled()) {
            if ($request->routeIs('2fa.*', 'profile.*', 'logout')) {
                return $next($request);
            }

            return redirect()->route('2fa.setup')
                ->with('warning', 'Your role requires two-factor authentication. Please set it up to continue.');
        }

        return $next($request);
    }
}
