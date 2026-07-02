<?php

namespace Tests\Feature\Api;

use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectApiControllerTest extends TestCase
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

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('depends_on')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
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

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('lead_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('guide')->nullable();
            $table->string('github_repo')->nullable();
            $table->string('stack')->nullable();
            $table->string('health')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('project_team', function (Blueprint $table): void {
            $table->foreignId('project_id');
            $table->foreignId('team_id');
            $table->primary(['project_id', 'team_id']);
        });

        Schema::create('sprints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('deadline')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('backlog_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('sprint_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('guide')->nullable();
            $table->enum('status', ['raw', 'refined'])->default('raw');
            $table->boolean('promoted')->default(false);
            $table->unsignedBigInteger('promoted_task_id')->nullable();
            $table->timestamp('promoted_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_index_show_and_project_scoped_sprint_backlog_endpoints_work(): void
    {
        $role = Role::query()->create([
            'name' => 'Manager',
            'slug' => 'manager',
        ]);

        $permission = Permission::query()->create([
            'name' => 'View All Projects',
            'slug' => 'projects.view_all',
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::factory()->create(['role_id' => $role->id]);

        $activeProject = Project::query()->create([
            'name' => 'Alpha',
            'description' => 'Alpha desc',
            'guide' => 'Alpha guide',
            'status' => 'active',
            'health' => 'on-track',
            'stack' => 'Laravel',
            'github_repo' => 'https://github.com/PiShift/alpha',
        ]);

        Project::query()->create([
            'name' => 'Completed',
            'status' => 'complete',
        ]);

        $indexResponse = $this->actingAs($user)->getJson('/api/projects');
        $indexResponse->assertOk()->assertJsonCount(1);
        $indexResponse->assertJsonPath('0.id', $activeProject->id);

        $showResponse = $this->actingAs($user)->getJson('/api/projects/'.$activeProject->id);
        $showResponse->assertOk()->assertJsonPath('id', $activeProject->id);
        $showResponse->assertJsonPath('guide', 'Alpha guide');

        $storeSprintResponse = $this->actingAs($user)->postJson('/api/projects/'.$activeProject->id.'/sprints', [
            'name' => 'Sprint 1',
            'description' => 'First sprint',
            'deadline' => '2026-08-01',
        ]);

        $storeSprintResponse->assertCreated();
        $sprintId = (int) $storeSprintResponse->json('id');

        $sprintsResponse = $this->actingAs($user)->getJson('/api/projects/'.$activeProject->id.'/sprints');
        $sprintsResponse->assertOk()->assertJsonCount(1);
        $sprintsResponse->assertJsonPath('0.id', $sprintId);

        $storeBacklogResponse = $this->actingAs($user)->postJson('/api/projects/'.$activeProject->id.'/backlog', [
            'title' => 'Backlog item',
            'description' => 'Backlog description',
            'sprint_id' => $sprintId,
        ]);

        $storeBacklogResponse->assertCreated();
        $storeBacklogResponse->assertJsonPath('title', 'Backlog item');
        $storeBacklogResponse->assertJsonPath('sprint_id', $sprintId);
    }
}
