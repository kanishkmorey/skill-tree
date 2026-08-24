<?php

namespace Database\Factories;

use App\Enums\KnowledgeStatus;
use App\Models\Skill;
use App\Models\SkillKnowledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillKnowledge>
 */
class SkillKnowledgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'skill_id' => Skill::factory(),
            'title' => fake()->words(3, true),
            'status' => fake()->randomElement(KnowledgeStatus::cases())->value,
        ];
    }
}
