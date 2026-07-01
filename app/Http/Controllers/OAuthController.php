<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OAuthController extends Controller
{
    public function authorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer' => 'https://dev.pishift.co',
            'authorization_endpoint' => 'https://dev.pishift.co/oauth/authorize',
            'token_endpoint' => 'https://dev.pishift.co/oauth/token',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'code_challenge_methods_supported' => ['S256'],
            'client_id_metadata_document_supported' => true,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function authorize(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login');
        }

        $payload = $request->validate([
            'code_challenge' => ['required', 'string', 'min:43', 'max:128'],
            'code_challenge_method' => ['required', 'string', 'in:S256'],
            'state' => ['required', 'string', 'max:255'],
            'redirect_uri' => [
                'required',
                'string',
                'max:2048',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    $parts = parse_url((string) $value);

                    if (! is_array($parts) || empty($parts['scheme'])) {
                        $fail('The '.$attribute.' is invalid.');
                    }
                },
            ],
        ]);

        $request->session()->put('oauth.authorize_request', $payload);

        return view('oauth.authorize');
    }

    public function handleAuthorize(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            $request->session()->put('url.intended', url('/oauth/authorize'));

            return redirect()->route('login');
        }

        $action = $request->validate([
            'action' => ['required', 'string', 'in:allow,deny'],
        ])['action'];

        $storedRequest = $request->session()->get('oauth.authorize_request');

        if (! is_array($storedRequest)) {
            abort(422, 'Missing authorization request context.');
        }

        $redirectUri = (string) ($storedRequest['redirect_uri'] ?? '');
        $state = (string) ($storedRequest['state'] ?? '');

        if ($action === 'deny') {
            $request->session()->forget('oauth.authorize_request');

            return redirect()->away($this->appendQueryString($redirectUri, [
                'error' => 'access_denied',
                'state' => $state,
            ]));
        }

        $code = Str::random(64);
        $expiresAt = now()->addMinutes(5);

        Cache::put('oauth_code_'.$code, [
            'user_id' => $request->user()->id,
            'code_challenge' => (string) $storedRequest['code_challenge'],
            'expires_at' => $expiresAt->toIso8601String(),
        ], $expiresAt);

        $request->session()->forget('oauth.authorize_request');

        return redirect()->away($this->appendQueryString($redirectUri, [
            'code' => $code,
            'state' => $state,
        ]));
    }

    public function token(Request $request, TokenService $tokenService): JsonResponse
    {
        $payload = $request->validate([
            'grant_type' => ['required', 'string', 'in:authorization_code'],
            'code' => ['required', 'string'],
            'code_verifier' => ['required', 'string', 'min:43', 'max:128'],
        ]);

        $cacheKey = 'oauth_code_'.$payload['code'];
        $record = Cache::get($cacheKey);

        if (! is_array($record)) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $expiresAt = data_get($record, 'expires_at');
        if (! is_string($expiresAt) || now()->gte($expiresAt)) {
            Cache::forget($cacheKey);

            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $storedChallenge = (string) data_get($record, 'code_challenge', '');
        $calculatedChallenge = $this->pkceChallengeFromVerifier($payload['code_verifier']);

        if (! hash_equals($storedChallenge, $calculatedChallenge)) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $user = User::query()->whereKey(data_get($record, 'user_id'))->first();

        if (! $user) {
            Cache::forget($cacheKey);

            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $generated = $tokenService->generatePAT();

        PersonalAccessToken::create([
            'user_id' => $user->id,
            'name' => 'OAuth MCP',
            'token_hash' => $generated['hash'],
            'token_prefix' => $generated['prefix'],
            'expires_at' => now()->addDays(90),
        ]);

        Cache::forget($cacheKey);

        return response()->json([
            'access_token' => $generated['raw'],
            'token_type' => 'bearer',
            'expires_in' => 7776000,
        ]);
    }

    private function appendQueryString(string $uri, array $parameters): string
    {
        $separator = str_contains($uri, '?') ? '&' : '?';

        return $uri.$separator.http_build_query($parameters);
    }

    private function pkceChallengeFromVerifier(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
