<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SqlReadControllerTest extends TestCase
{
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
            $table->timestamp('onboarded_at')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/sql/read', [
            'query' => 'SELECT 1 AS value',
        ])->assertUnauthorized();
    }

    public function test_it_runs_read_only_select_queries(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/sql/read', [
            'query' => 'SELECT 1 AS value',
        ]);

        $response->assertOk();
        $response->assertJsonPath('row_count', 1);
        $response->assertJsonPath('rows.0.value', 1);
    }

    public function test_it_blocks_non_read_queries(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/sql/read', [
            'query' => 'UPDATE users SET name = "hacked"',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Only read-only SELECT queries are allowed.');
    }
}
