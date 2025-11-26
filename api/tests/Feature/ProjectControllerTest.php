<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_projects()
    {
        $projects = Project::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.projects.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'created_at'],
            ],
        ]);

        $responseData = $response->json('data');
        $returnedIds = collect($responseData)->pluck('id')->sort()->values()->toArray();
        $createdIds = $projects->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($createdIds, $returnedIds);
    }

    public function test_show_returns_project()
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.projects.show', $project->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'created_at']
        ]);

        $response->assertJson([
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'created_at' => $project->created_at->toISOString(),
            ]
        ]);
    }

    public function test_store_creates_project()
    {
        $data = [
            'name' => 'New Project',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson(route('v1.projects.store'), $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'created_at']
        ]);

        $response->assertJson([
            'data' => [
                'name' => $data['name'],
            ]
        ]);

        $this->assertDatabaseHas('projects', $data);
    }

    public function test_update_updates_project()
    {
        $project = Project::factory()->create();

        $data = [
            'name' => 'Updated Project',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->putJson(route('v1.projects.update', $project->id), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'name' => $data['name'],
            ]
        ]);

        $project->refresh();
        $this->assertEquals($data['name'], $project->name);
    }

    public function test_destroy_deletes_project()
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson(route('v1.projects.destroy', $project->id));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_tasks_returns_tasks_for_project()
    {
        $project = Project::factory()->create();
        $tasks = Task::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        Task::factory()->count(2)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('v1.projects.tasks', $project->id));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'description', 'status', 'created_at']
            ]
        ]);

        $responseData = $response->json('data');
        $returnedIds = collect($responseData)->pluck('id')->sort()->values()->toArray();
        $expectedIds = $tasks->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($expectedIds, $returnedIds);
    }

}
