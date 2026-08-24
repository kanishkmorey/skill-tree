<?php

namespace Database\Factories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(['article', 'book', 'video', 'audio', 'course', 'blog', 'rss feed']),
            'url' => fake()->url(),
            'notes' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['not-started', 'in-progress', 'completed']),
            'workspace_id' => fake()->numberBetween(1, 1000),
            'created_by' => fake()->numberBetween(1, 1000),
        ];
    }
}
