<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiSessionOrPatAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        $authHeader = (string) $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $this->unauthorized();
        }

        $token = trim($matches[1]);

        if (! str_starts_with($token, 'mul_')) {
            return $this->unauthorized();
        }

        $hash = hash('sha256', $token);
        $cacheKey = 'pat:' . $hash;
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            $personalToken = PersonalAccessToken::query()
                ->where('token_hash', $hash)
                ->active()
                ->first();

            if (! $personalToken) {
                return $this->unauthorized();
            }

            $userId = $personalToken->user_id;
            Cache::put($cacheKey, $userId, now()->addMinutes(10));
        }

        $user = User::find($userId);

        if (! $user) {
            return $this->unauthorized();
        }

        app()->terminating(static function () use ($hash): void {
            PersonalAccessToken::where('token_hash', $hash)->update(['last_used_at' => now()]);
        });

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['error' => 'unauthorized'], 401);
    }
}
