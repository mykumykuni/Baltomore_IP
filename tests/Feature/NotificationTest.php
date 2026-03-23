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

class NotificationTest extends TestCase
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

    public function test_stores_task_activity_notification_when_creating_task(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'name' => 'Jane Smith',
            'age' => 28,
            'birthdate' => '1998-05-12',
            'email' => 'jane@example.com',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_lists_and_marks_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $task = Task::create([
            'name' => 'Mark Read Test',
            'age' => 30,
            'birthdate' => '1996-01-01',
            'email' => 'mark-read@example.com',
        ]);

        $user->notify(new \App\Notifications\TaskActivityNotification($task, 'created', $user));

        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications');

        $notificationId = $user->fresh()->notifications()->first()->id;

        $this->postJson("/api/notifications/{$notificationId}/read")
            ->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }
}
