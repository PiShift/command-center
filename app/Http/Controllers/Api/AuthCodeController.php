<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AuthCodeController extends Controller
{
    public function sendCode(Request $request, TokenService $tokenService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim((string) $data['email']));

        $recentCodeExists = VerificationCode::query()
            ->where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->where('created_at', '>', now()->subSeconds(60))
            ->exists();

        if ($recentCodeExists) {
            return response()->json(['error' => 'Please wait before requesting another code'], 429);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return response()->json(['status' => 'ok']);
        }

        $code = $tokenService->generateOTP();

        VerificationCode::create([
            'email'      => $email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::raw(
            'Your login code is: ' . $code . '. It expires in 10 minutes.',
            static function ($message) use ($email): void {
                $message->to($email)->subject('Your Command Center login code');
            }
        );

        return response()->json(['status' => 'ok']);
    }

    public function verifyCode(Request $request, TokenService $tokenService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'string', 'size:6'],
        ]);

        $email = strtolower(trim((string) $data['email']));
        $code = trim((string) $data['code']);

        $record = VerificationCode::valid($email)->first();

        if (! $record) {
            return response()->json(['error' => 'Invalid or expired code'], 401);
        }

        if ($record->code !== $code) {
            $record->increment('attempts');
            return response()->json(['error' => 'Invalid code'], 401);
        }

        $record->update(['used' => true]);

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return response()->json(['error' => 'User not found'], 401);
        }

        $generated = $tokenService->generatePAT();

        PersonalAccessToken::create([
            'user_id'      => $user->id,
            'name'         => 'CLI login',
            'token_hash'   => $generated['hash'],
            'token_prefix' => $generated['prefix'],
            'expires_at'   => now()->addDays(90),
        ]);

        return response()->json([
            'token' => $generated['raw'],
            'user'  => [
                'id'    => (string) $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
