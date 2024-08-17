<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles y permisos
        $userRole = Role::create(['name' => 'user']);
        $adminRole = Role::create(['name' => 'admin']);

        $permissions = [
            'create tasks',
            'read tasks',
            'update tasks',
            'delete tasks'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $userRole->givePermissionTo($permissions);
        $adminRole->givePermissionTo($permissions);

        // Crear usuario y asignar rol
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->user->givePermissionTo($permissions); // Asegúrate de que el usuario tenga los permisos directamente
        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_can_create_task_with_valid_data()
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'This is a test task',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task created successfully',
            ]);

        $this->assertDatabaseHas('tasks', $taskData);
    }

    public function test_cannot_create_task_with_invalid_data()
    {
        $taskData = [
            'title' => '',
            'description' => 'This is a test task',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('tasks', $taskData);
    }

    public function test_can_update_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $updatedData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated task description',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/tasks/{$task->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task updated successfully',
            ]);

        $this->assertDatabaseHas('tasks', $updatedData);
    }

    public function test_can_delete_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
    
}