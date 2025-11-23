<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_tasks()
    {
        $tasks = Task::factory()->count(5)->create();

        $response = $this->getJson(route('v1.tasks.index'));

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

        $response = $this->getJson(route('v1.tasks.show', $task->id));

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
        ];

        $response = $this->postJson(route('v1.tasks.store'), $data);

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

        $response = $this->putJson(route('v1.tasks.update', $task->id), $data);

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
        $response = $this->deleteJson(route('v1.tasks.destroy', $task->id));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_update_requires_valid_status()
    {
        $task = Task::factory()->create();

        $response = $this->putJson(route('v1.tasks.update', $task->id), [
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

        $response = $this->putJson(route('v1.tasks.update', $task->id), [
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
        $response = $this->putJson(route('v1.tasks.update', $task->id), $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $data['title'],
            'description' => null,
        ]);
    }

    public function test_store_requires_title()
    {
        $response = $this->postJson(route('v1.tasks.store'), [
            'description' => 'Task description',
            'status' => 'open',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_store_requires_valid_status()
    {
        $response = $this->postJson(route('v1.tasks.store'), [
            'title' => 'Test Task',
            'description' => 'Task description',
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_store_title_max_length()
    {
        $response = $this->postJson(route('v1.tasks.store'), [
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
        ];
        $response = $this->postJson(route('v1.tasks.store'), $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', [
            'title' => $data['title'],
            'description' => null,
        ]);
    }

    public function test_store_requires_status()
    {
        $response = $this->postJson(route('v1.tasks.store'), [
            'title' => 'Test Task',
            'description' => 'Task description',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }
}
