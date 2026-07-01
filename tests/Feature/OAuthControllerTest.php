<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OAuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->string('color')->nullable();
                $table->string('initials')->nullable();
                $table->timestamp('onboarded_at')->nullable();
                $table->json('notification_preferences')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->unsignedBigInteger('user_id');
                $table->string('name');
                $table->string('token_hash');
                $table->string('token_prefix')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->boolean('revoked')->default(false);
                $table->timestamps();
            });
        }
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => 'user'.uniqid().'@example.test',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_authorization_server_endpoint_returns_expected_payload(): void
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server');

        $response->assertOk()->assertJson([
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

    public function test_authorize_redirects_guest_to_login(): void
    {
        $response = $this->get('/oauth/authorize?code_challenge=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa&code_challenge_method=S256&state=abc&redirect_uri=https://example.test/callback');

        $response->assertRedirect(route('login'));
    }

    public function test_authorize_shows_consent_and_stores_request_in_session(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/oauth/authorize?code_challenge=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa&code_challenge_method=S256&state=abc&redirect_uri=https://example.test/callback');

        $response->assertOk()->assertSee('Claude MCP wants to access your Command Center.');

        $this->assertSame('abc', session('oauth.authorize_request.state'));
        $this->assertSame('S256', session('oauth.authorize_request.code_challenge_method'));
        $this->assertSame('https://example.test/callback', session('oauth.authorize_request.redirect_uri'));
    }

    public function test_handle_authorize_generates_code_and_redirects(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->withSession([
            'oauth.authorize_request' => [
                'code_challenge' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'code_challenge_method' => 'S256',
                'state' => 'state-123',
                'redirect_uri' => 'https://example.test/callback',
            ],
        ]);

        $response = $this->post('/oauth/authorize', ['action' => 'allow']);

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

        $this->assertSame('state-123', $query['state'] ?? null);
        $this->assertNotEmpty($query['code'] ?? null);

        $cached = Cache::get('oauth_code_'.$query['code']);
        $this->assertIsArray($cached);
        $this->assertSame($user->id, $cached['user_id']);
        $this->assertSame('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $cached['code_challenge']);
    }

    public function test_token_returns_access_token_for_valid_code_and_verifier(): void
    {
        $user = $this->createUser();

        $verifier = str_repeat('a', 64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        Cache::put('oauth_code_test-code', [
            'user_id' => $user->id,
            'code_challenge' => $challenge,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ], now()->addMinutes(5));

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => 'test-code',
            'code_verifier' => $verifier,
        ]);

        $response->assertOk()->assertJsonPath('token_type', 'bearer');
        $response->assertJsonPath('expires_in', 7776000);

        $accessToken = $response->json('access_token');
        $this->assertIsString($accessToken);
        $this->assertStringStartsWith('mul_', $accessToken);
        $this->assertNull(Cache::get('oauth_code_test-code'));
    }

    public function test_token_rejects_invalid_verifier(): void
    {
        $user = $this->createUser();

        Cache::put('oauth_code_bad-code', [
            'user_id' => $user->id,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('x', 64), true)), '+/', '-_'), '='),
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ], now()->addMinutes(5));

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => 'bad-code',
            'code_verifier' => str_repeat('y', 64),
        ]);

        $response->assertStatus(400)->assertJson([
            'error' => 'invalid_grant',
        ]);
    }
}
