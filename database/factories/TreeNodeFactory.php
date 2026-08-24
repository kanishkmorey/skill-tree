<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\Tree;
use App\Models\TreeNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreeNode>
 */
class TreeNodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tree_id' => Tree::factory(),
            'skill_id' => Skill::factory(),
            'parent_node_id' => null,
        ];
    }
}
