<?php

namespace App\Http\Middleware;

use App\Models\DaemonToken;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DaemonTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = (string) $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $this->unauthorized();
        }

        $token = trim($matches[1]);

        if (! str_starts_with($token, 'mdt_')) {
            return $this->unauthorized();
        }

        $hash = hash('sha256', $token);
        $cacheKey = 'daemon_token:' . $hash;
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            $daemonToken = DaemonToken::with('user')
                ->where('token_hash', $hash)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if (! $daemonToken || ! $daemonToken->user) {
                return $this->unauthorized();
            }

            $daemonToken->forceFill(['last_used_at' => now()])->save();
            $userId = $daemonToken->user_id;
            Cache::put($cacheKey, $userId, now()->addMinutes(10));
        }

        $user = User::find($userId);

        if (! $user) {
            return $this->unauthorized();
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['error' => 'unauthorized'], 401);
    }
}