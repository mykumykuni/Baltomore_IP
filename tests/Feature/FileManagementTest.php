<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FileManagementTest extends TestCase
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

    public function test_can_upload_and_list_task_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $task = Task::create([
            'name' => 'File Upload Task',
            'age' => 25,
            'birthdate' => '2000-01-01',
            'email' => 'upload-file@example.com',
            'user_id' => $user->id,
        ]);

        $uploadResponse = $this->postJson("/api/tasks/{$task->id}/files", [
            'file' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain'),
        ]);

        $uploadResponse
            ->assertCreated()
            ->assertJsonPath('task_id', $task->id)
            ->assertJsonPath('original_name', 'notes.txt');

        $storedPath = $uploadResponse->json('file_path');
        $this->assertTrue(Storage::disk('local')->exists($storedPath));

        $this->getJson("/api/tasks/{$task->id}/files")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.original_name', 'notes.txt');
    }

    public function test_can_download_and_delete_task_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $task = Task::create([
            'name' => 'File Delete Task',
            'age' => 30,
            'birthdate' => '1995-05-05',
            'email' => 'delete-file@example.com',
            'user_id' => $admin->id,
        ]);

        $uploadResponse = $this->postJson("/api/tasks/{$task->id}/files", [
            'file' => UploadedFile::fake()->create('report.pdf', 12, 'application/pdf'),
        ])->assertCreated();

        $taskFileId = $uploadResponse->json('id');
        $storedPath = $uploadResponse->json('file_path');

        $this->get("/api/tasks/{$task->id}/files/{$taskFileId}/download")
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->deleteJson("/api/tasks/{$task->id}/files/{$taskFileId}")
            ->assertOk()
            ->assertJsonPath('message', 'File deleted');

        $this->assertFalse(Storage::disk('local')->exists($storedPath));
        $this->assertDatabaseMissing('task_files', ['id' => $taskFileId]);
    }
}
