<?php

namespace Database\Factories;

use App\Models\Requirement;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Requirement>
 */
class RequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'fulfilled' => fake()->boolean(),
            'skill_id' => Skill::factory(),
        ];
    }
}
