<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence,
            'description' => fake()->paragraph,
            'status' => fake()->randomElement(['open', 'in_progress', 'done']),
            'deadline' => fake()->optional()->dateTime,
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
        ];
    }
}
