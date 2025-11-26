<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = Task::where('user_id', auth()->id())->get();
        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $task = Task::create($request->validated());
        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());
        return new TaskResource($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return response()->noContent();
    }

    /**
     * Get all tasks assigned to a specific user.
     *
     * @param int $userId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function tasksByUser(int $userId)
    {
        if ($userId !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden.'
            ], 403);
        }
        $tasks = Task::where('user_id', $userId)->get();
        return TaskResource::collection($tasks);
    }

    /**
     * Get all tasks associated with a specific project.
     *
     * @param int $projectId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function tasksByProject(int $projectId)
    {
        $tasks = Task::where('project_id', $projectId)
            ->where('user_id', auth()->id())
            ->get();
        return TaskResource::collection($tasks);
    }

    /**
     * Get all tasks that are overdue (deadline in the past).
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function overdueTasks()
    {
        $tasks = Task::where('deadline', '<', now())
            ->where('user_id', auth()->id())
            ->get();
        return TaskResource::collection($tasks);
    }
}
