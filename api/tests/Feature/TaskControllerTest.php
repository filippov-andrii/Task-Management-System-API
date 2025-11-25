<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Notifications\TaskDeadlinePassedNotification;
use Illuminate\Support\Facades\Notification;

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
        $tasks = Task::factory()->count(5)->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson(route('v1.tasks.destroy', $task->id));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_update_requires_valid_status()
    {
        $task = Task::factory()->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();
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
        $task = Task::factory()->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();

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
        $task = Task::factory()->for($this->user, 'user')->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), [
                'deadline' => 'not-a-date'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deadline']);
    }

    public function test_update_fails_with_invalid_project_id()
    {
        $task = Task::factory()->for($this->user, 'user')->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), [
                'project_id' => 999999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_id']);
    }

    public function test_tasks_by_user_returns_only_user_tasks()
    {
        $otherUser = User::factory()->create();

        $userTasks = Task::factory()->count(3)->create(['user_id' => $this->user->id]);
        //other tasks
        Task::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('v1.tasks.byUser', $this->user->id));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        $returnedIds = collect($response->json('data'))->pluck('id')->sort()->values()->toArray();
        $expectedIds = $userTasks->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($expectedIds, $returnedIds);
    }

    public function test_tasks_by_project_returns_only_project_tasks()
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        $projectTasks = Task::factory()->count(4)->for($this->user, 'user')->create(['project_id' => $project->id]);
        //other tasks
        Task::factory()->count(2)->for($this->user, 'user')->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('v1.tasks.byProject', $project->id));

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');

        $returnedIds = collect($response->json('data'))->pluck('id')->sort()->values()->toArray();
        $expectedIds = $projectTasks->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($expectedIds, $returnedIds);
    }

    public function test_overdue_tasks_returns_only_tasks_with_past_deadline()
    {
        $pastTasks = Task::factory()->for($this->user, 'user')->count(3)->create([
            'deadline' => now()->subDays(2),
        ]);
        //future tasks
        Task::factory()->count(2)->for($this->user, 'user')->create([
            'deadline' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('v1.tasks.overdue'));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        $returnedIds = collect($response->json('data'))->pluck('id')->sort()->values()->toArray();
        $expectedIds = $pastTasks->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($expectedIds, $returnedIds);
    }

    public function test_user_cannot_view_other_users_tasks_in_index()
    {
        $otherUser = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.tasks.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_view_other_users_tasks_in_show()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.tasks.show', $task->id));

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden. You do not own this task.'
        ]);
    }

    public function test_user_cannot_update_task_of_another_user()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), [
                'title' => 'Hacked Title'
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden. You do not own this task.'
        ]);
    }

    public function test_user_cannot_delete_task_of_another_user()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('v1.tasks.destroy', $task->id));

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden. You do not own this task.'
        ]);
    }

    public function test_user_cannot_view_other_users_tasks_in_by_user_endpoint()
    {
        $otherUser = User::factory()->create();
        Task::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('v1.tasks.byUser', $otherUser->id));

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden.'
        ]);
    }

    public function test_user_cannot_view_other_users_tasks_in_by_project_endpoint()
    {
        $project = Project::factory()->create();
        $otherUser = User::factory()->create();

        $task1 = Task::factory()->create(['user_id' => $this->user->id, 'project_id' => $project->id]);
        $task2 = Task::factory()->create(['user_id' => $otherUser->id, 'project_id' => $project->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.tasks.byProject', $project->id));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $task1->id]);
        $response->assertJsonMissing(['id' => $task2->id]);
    }

    public function test_user_cannot_view_other_users_tasks_in_by_overdue_endpoint()
    {
        $otherUser = User::factory()->create();

        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'deadline' => now()->subDay(),
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $otherUser->id,
            'deadline' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(route('v1.tasks.overdue'));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $task1->id]);
        $response->assertJsonMissing(['id' => $task2->id]);
    }

    public function test_user_cannot_update_overdue_task()
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'deadline' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), ['status' => 'done']);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_overdue_task_of_another_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
            'deadline' => now()->subDays(2), // просроченная
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), ['status' => 'done']);

        $response->assertStatus(200);
        $this->assertEquals('done', $task->refresh()->status);
    }

    public function test_admin_cannot_update_not_overdue_task_of_another_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
            'deadline' => now()->addDays(2), // просроченная
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson(route('v1.tasks.update', $task->id), ['status' => 'done']);

        $response->assertStatus(403);
    }

    public function test_task_deadline_notification_is_sent()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => now()->subDays(1)
        ]);

        Notification::fake();
        $task->update(['description' => 'Updated description']);
        Notification::assertSentTo(
            [$user], TaskDeadlinePassedNotification::class
        );
    }

}
