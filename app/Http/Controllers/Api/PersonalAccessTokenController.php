<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PersonalAccessTokenController extends Controller
{
    public function store(Request $request, TokenService $tokenService): JsonResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $generated = $tokenService->generatePAT();
        $expiresAt = isset($data['expires_in_days']) ? now()->addDays((int) $data['expires_in_days']) : null;

        $token = PersonalAccessToken::create([
            'user_id'      => $request->user()->id,
            'name'         => $data['name'],
            'token_hash'   => $generated['hash'],
            'token_prefix' => $generated['prefix'],
            'expires_at'   => $expiresAt,
        ]);

        return response()->json([
            'id'          => $token->id,
            'name'        => $token->name,
            'token_prefix'=> $token->token_prefix,
            'expires_at'  => optional($token->expires_at)->toISOString(),
            'last_used_at'=> null,
            'created_at'  => $token->created_at->toISOString(),
            'token'       => $generated['raw'],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $tokens = PersonalAccessToken::query()
            ->where('user_id', $request->user()->id)
            ->active()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'token_prefix', 'expires_at', 'last_used_at', 'created_at']);

        return response()->json(
            $tokens->map(static fn (PersonalAccessToken $token) => [
                'id'           => $token->id,
                'name'         => $token->name,
                'token_prefix' => $token->token_prefix,
                'expires_at'   => optional($token->expires_at)->toISOString(),
                'last_used_at' => optional($token->last_used_at)->toISOString(),
                'created_at'   => $token->created_at->toISOString(),
            ])->values()
        );
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $token = PersonalAccessToken::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $token) {
            return response()->json(['error' => 'not found'], 404);
        }

        $token->update(['revoked' => true]);
        Cache::forget('pat:' . $token->token_hash);

        return response()->json(['status' => 'ok']);
    }
}
