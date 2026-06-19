<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
        ]);

        $user = $request->user();

        if (array_key_exists('name', $data)) {
            $user->name = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }

        if ($user->isDirty()) {
            $user->save();
        }

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    public function updateOnboarding(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function cloudWaitlist(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function cliToken(Request $request, TokenService $tokenService): JsonResponse
    {
        $generated = $tokenService->generatePAT();

        $token = PersonalAccessToken::create([
            'user_id'      => $request->user()->id,
            'name'         => 'CLI Token',
            'token_hash'   => $generated['hash'],
            'token_prefix' => $generated['prefix'],
        ]);

        Cache::forget('pat:' . $generated['hash']);

        return response()->json(['token' => $generated['raw']]);
    }
}
