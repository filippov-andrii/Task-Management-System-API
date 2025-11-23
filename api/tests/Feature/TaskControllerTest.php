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

        $response = $this->getJson(route('tasks.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(5);
        $response->assertJsonStructure([
            '*' => ['id', 'title', 'description', 'status', 'created_at', 'updated_at'],
        ]);

        $responseData = $response->json();
        $returnedIds = collect($responseData)->pluck('id')->sort()->values()->toArray();
        $createdIds = $tasks->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($createdIds, $returnedIds);
    }

    public function test_show_returns_task()
    {
        $task = Task::factory()->create();

        $response = $this->getJson(route('tasks.show', $task->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'title', 'description', 'status', 'created_at', 'updated_at',
        ]);
        $response->assertJson([
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
        ]);
    }

    public function test_store_creates_task()
    {
        $data = [
            'title' => 'New Task',
            'description' => 'This is a new task.',
            'status' => 'open',
        ];

        $response = $this->postJson(route('tasks.store'), $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'title', 'description', 'status', 'created_at', 'updated_at'
        ]);
        $response->assertJson([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
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

        $response = $this->putJson(route('tasks.update', $task->id), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
        ]);

        $task->refresh();
        $this->assertEquals($data['title'], $task->title);
        $this->assertEquals($data['description'], $task->description);
        $this->assertEquals($data['status'], $task->status);
    }

    public function test_destroy_deletes_task()
    {
        $task = Task::factory()->create();
        $response = $this->deleteJson(route('tasks.destroy', $task->id));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
