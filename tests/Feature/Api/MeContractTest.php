<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeContractTest extends TestCase
{
    private const REQUIRED_KEYS = [
        'id',
        'name',
        'email',
        'avatar_url',
        'onboarded_at',
        'onboarding_questionnaire',
        'starter_content_state',
        'language',
        'profile_description',
        'timezone',
        'created_at',
        'updated_at',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->string('color')->nullable();
            $table->string('initials')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_get_me_returns_all_required_schema_keys(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();

        $payload = $response->json();

        foreach (self::REQUIRED_KEYS as $key) {
            $this->assertArrayHasKey($key, $payload, "GET /api/me response missing key: {$key}");
        }

        $this->assertSame((string) $user->id, $payload['id']);
        $this->assertSame($user->name, $payload['name']);
        $this->assertSame($user->email, $payload['email']);
        $this->assertIsString($payload['profile_description']);
        $this->assertIsObject((object) $payload['onboarding_questionnaire']);
        $this->assertNull($payload['avatar_url']);
        $this->assertNull($payload['onboarded_at']);
        $this->assertNotEmpty($payload['created_at']);
        $this->assertNotEmpty($payload['updated_at']);
    }

    public function test_post_me_onboarding_complete_returns_all_required_schema_keys(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/me/onboarding/complete');

        $response->assertOk();

        $payload = $response->json();

        foreach (self::REQUIRED_KEYS as $key) {
            $this->assertArrayHasKey($key, $payload, "POST /api/me/onboarding/complete response missing key: {$key}");
        }

        $this->assertSame((string) $user->id, $payload['id']);
        $this->assertNull($payload['avatar_url']);
        $this->assertNull($payload['onboarded_at']);
        $this->assertSame('', $payload['profile_description']);
    }

    public function test_get_me_and_onboarding_complete_return_identical_schema_shape(): void
    {
        $user = User::factory()->create();

        $me = $this->actingAs($user)->getJson('/api/me')->json();
        $complete = $this->actingAs($user)->postJson('/api/me/onboarding/complete')->json();

        $this->assertSame(array_keys($me), array_keys($complete));
    }
}
