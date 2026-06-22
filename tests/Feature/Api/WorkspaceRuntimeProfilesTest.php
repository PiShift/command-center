<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkspaceRuntimeProfilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_api_workspace_runtime_profiles_returns_empty_json_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/workspaces/1/runtime-profiles');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertExactJson([]);
    }
}
