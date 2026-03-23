<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EloquentOrmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['view tasks', 'create tasks', 'edit tasks', 'delete tasks'] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::firstOrCreate(['name' => 'admin'])
            ->syncPermissions(['view tasks', 'create tasks', 'edit tasks', 'delete tasks']);

        Role::firstOrCreate(['name' => 'user'])
            ->syncPermissions(['view tasks', 'create tasks']);
    }

    public function test_task_is_owned_by_authenticated_user_on_create(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'name' => 'Owned Task',
            'age' => 21,
            'birthdate' => '2004-01-01',
            'email' => 'owned-task@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);

        $taskId = $response->json('id');

        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'user_id' => $user->id,
        ]);
    }

    public function test_task_index_can_filter_by_search_and_age(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();

        Task::create([
            'name' => 'Alice Example',
            'age' => 19,
            'birthdate' => '2006-01-01',
            'email' => 'alice@example.com',
            'user_id' => $owner->id,
        ]);

        Task::create([
            'name' => 'Bob Example',
            'age' => 35,
            'birthdate' => '1990-01-01',
            'email' => 'bob@example.com',
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/tasks?search=alice&min_age=18&max_age=25')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Alice Example')
            ->assertJsonPath('0.user.id', $owner->id);
    }
}
