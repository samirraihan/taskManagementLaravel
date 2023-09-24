<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => 1, // Replace with a valid task_id as needed.
            'user_id' => 1, // Replace with a valid user_id as needed.
            'body' => $this->faker->paragraph,
        ];
    }
}
