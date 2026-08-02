<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSecretKey
{
    public function handle(Request $request, Closure $next)
    {
        $configured = (string) config('services.api.secret_key');
        $header     = (string) $request->header('Authorization', '');

        if ($configured === '') {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        if (! str_starts_with($header, 'Bearer ')) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $incoming = substr($header, 7);

        if (! hash_equals($configured, $incoming)) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
