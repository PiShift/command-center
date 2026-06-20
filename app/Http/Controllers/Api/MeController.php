<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    public function completeOnboarding(Request $request): JsonResponse
    {
        // When an onboarded_at column is added, mark it here:
        // $user->forceFill(['onboarded_at' => now()])->save();
        return response()->json($this->userPayload($request->user()));
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

        return response()->json($this->userPayload($user));
    }

    public function updateOnboarding(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'                       => (string) $user->id,
            'name'                     => (string) $user->name,
            'email'                    => (string) $user->email,
            'avatar_url'               => $user->getAttribute('avatar_url') ?? null,
            'onboarded_at'             => optional($user->getAttribute('onboarded_at'))?->toIso8601String() ?? null,
            'onboarding_questionnaire' => (object) [],
            'starter_content_state'    => $user->getAttribute('starter_content_state') ?? null,
            'language'                 => $user->getAttribute('language') ?? null,
            'profile_description'      => (string) ($user->getAttribute('profile_description') ?? ''),
            'timezone'                 => $user->getAttribute('timezone') ?? null,
            'created_at'               => optional($user->created_at)?->toIso8601String() ?? '',
            'updated_at'               => optional($user->updated_at)?->toIso8601String() ?? '',
        ];
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
