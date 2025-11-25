<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_tasks()
    {
        $tasks = Task::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.tasks.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'description', 'status', 'created_at'],
            ],
        ]);

        $responseData = $response->json('data');
        $returnedIds = collect($responseData)->pluck('id')->sort()->values()->toArray();
        $createdIds = $tasks->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($createdIds, $returnedIds);
    }

    public function test_show_returns_task()
    {
        $task = Task::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.tasks.show', $task->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id', 'title', 'description', 'status', 'created_at',
            ]
        ]);
        $response->assertJson([
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'created_at' => $task->created_at->toISOString(),
            ]
        ]);
    }

    public function test_store_creates_task()
    {
        $data = [
            'title' => 'New Task',
            'description' => 'This is a new task.',
            'status' => 'open',
            'user_id' => $this->user->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id', 'title', 'description', 'status', 'created_at'
            ]
        ]);
        $response->assertJson([
            'data' => [
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
            ]
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
        ]);
    }

    public function test_update_updates_task()
    {
        $task = Task::factory()->create();

        $data = [
            'title' => 'Updated Task',
            'description' => 'Updated description.',
            'status' => 'in_progress',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->putJson(route('v1.tasks.update', $task->id), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
            ]
        ]);

        $task->refresh();
        $this->assertEquals($data['title'], $task->title);
        $this->assertEquals($data['description'], $task->description);
        $this->assertEquals($data['status'], $task->status);
    }

    public function test_destroy_deletes_task()
    {
        $task = Task::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson(route('v1.tasks.destroy', $task->id));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_update_requires_valid_status()
    {
        $task = Task::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->putJson(route('v1.tasks.update', $task->id), [
            'title' => 'Updated Task Title',
            'description' => 'Updated description',
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_update_title_max_length()
    {
        $task = Task::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->putJson(route('v1.tasks.update', $task->id), [
            'title' => str_repeat('a', 256),
            'description' => 'Updated description',
            'status' => 'in_progress',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_update_description_is_nullable()
    {
        $task = Task::factory()->create();
        $data = [
            'title' => 'Updated Task Title',
            'description' => '',
            'status' => 'open',
        ];
        $response = $this->actingAs($this->user, 'sanctum')->putJson(route('v1.tasks.update', $task->id), $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $data['title'],
            'description' => null,
        ]);
    }

    public function test_store_requires_title()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson(route('v1.tasks.store'), [
            'description' => 'Task description',
            'status' => 'open',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_store_requires_valid_status()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson(route('v1.tasks.store'), [
            'title' => 'Test Task',
            'description' => 'Task description',
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_store_title_max_length()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson(route('v1.tasks.store'), [
            'title' => str_repeat('a', 256),
            'description' => 'Task description',
            'status' => 'open',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_store_description_is_nullable()
    {
        $data = [
            'title' => 'Test Task',
            'description' => '',
            'status' => 'open',
            'user_id' => $this->user->id,
        ];
        $response = $this->actingAs($this->user, 'sanctum')->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', [
            'title' => $data['title'],
            'description' => null,
        ]);
    }

    public function test_store_requires_status()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson(route('v1.tasks.store'), [
            'title' => 'Test Task',
            'description' => 'Task description',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_index_requires_authentication()
    {
        $response = $this->getJson(route('v1.tasks.index'));

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_show_requires_authentication()
    {
        $task = Task::factory()->create();

        $response = $this->getJson(route('v1.tasks.show', $task->id));

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_store_requires_authentication()
    {
        $response = $this->postJson(route('v1.tasks.store'), [
            'title' => 'New Task',
            'description' => 'Task description',
            'status' => 'open',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_update_requires_authentication()
    {
        $task = Task::factory()->create();

        $response = $this->putJson(route('v1.tasks.update', $task->id), [
            'title' => 'Updated Task',
            'description' => 'Updated description',
            'status' => 'in_progress',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_destroy_requires_authentication()
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson(route('v1.tasks.destroy', $task->id));

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_store_creates_task_with_user_project_and_deadline()
    {
        $user = $this->user;
        $project = Project::factory()->create();

        $data = [
            'title' => 'Task with full info',
            'description' => 'Task description with user, project and deadline',
            'status' => 'open',
            'user_id' => $user->id,
            'project_id' => $project->id,
            'deadline' => now()->addDays(5)->toDateTimeString(),
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id', 'title', 'description', 'status', 'deadline', 'user_id', 'project_id', 'created_at',
            ]
        ]);

        $responseData = $response->json('data');

        $this->assertEquals($data['title'], $responseData['title']);
        $this->assertEquals($data['description'], $responseData['description']);
        $this->assertEquals($data['status'], $responseData['status']);
        $this->assertEquals($data['user_id'], $responseData['user_id']);
        $this->assertEquals($data['project_id'], $responseData['project_id']);
        $this->assertEquals($data['deadline'], $responseData['deadline']);

        $this->assertDatabaseHas('tasks', [
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'],
            'deadline' => $data['deadline'],
        ]);
    }

    public function test_store_fails_when_user_id_is_missing()
    {
        $data = [
            'title' => 'Invalid Task',
            'description' => 'Missing user_id',
            'status' => 'open',
            // 'user_id' — absent
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    public function test_store_fails_when_user_id_does_not_exist()
    {
        $data = [
            'title' => 'Task',
            'description' => 'Invalid user id',
            'status' => 'open',
            'user_id' => 999999,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    public function test_store_fails_when_project_id_does_not_exist()
    {
        $data = [
            'title' => 'Task',
            'description' => 'Invalid project',
            'status' => 'open',
            'user_id' => $this->user->id,
            'project_id' => 999999,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_id']);
    }

    public function test_store_fails_when_deadline_is_invalid()
    {
        $data = [
            'title' => 'Task',
            'description' => 'Invalid deadline',
            'status' => 'open',
            'user_id' => $this->user->id,
            'project_id' => null,
            'deadline' => 'invalid-date-format',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deadline']);
    }

    public function test_update_fails_with_invalid_deadline()
    {
        $task = Task::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), [
                'deadline' => 'not-a-date'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deadline']);
    }

    public function test_update_fails_with_invalid_project_id()
    {
        $task = Task::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), [
                'project_id' => 999999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_id']);
    }
}
