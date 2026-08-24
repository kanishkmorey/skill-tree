<?php

use App\Models\Skill;
use App\Models\Tree;
use App\Models\TreeNode;

test('creates a root branch with a brand new skill', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);

    $response = $this->postJson("/api/v1/trees/{$tree->id}/nodes", [
        'name' => 'Root Skill',
    ], $headers)->assertCreated();

    $response->assertJsonPath('data.skill.name', 'Root Skill')
        ->assertJsonPath('data.parent_node_id', null);

    $this->assertDatabaseHas('skills', ['name' => 'Root Skill', 'workspace_id' => 111]);
});

test('creates a nested branch under a parent node', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);
    $root = TreeNode::factory()->create(['tree_id' => $tree->id]);

    $this->postJson("/api/v1/trees/{$tree->id}/nodes", [
        'name' => 'Child Skill',
        'parent_node_id' => $root->id,
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.parent_node_id', $root->id);
});

test('attaches an existing skill under a node', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);
    $root = TreeNode::factory()->create(['tree_id' => $tree->id]);
    $skill = Skill::factory()->create(['workspace_id' => 111]);

    $this->postJson("/api/v1/trees/{$tree->id}/nodes/{$root->id}/attach", [
        'skill_id' => $skill->id,
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.parent_node_id', $root->id)
        ->assertJsonPath('data.skill.id', $skill->id);
});

test('rejects attaching a skill that already has a node in the tree', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);
    $root = TreeNode::factory()->create(['tree_id' => $tree->id]);
    $existing = TreeNode::factory()->create(['tree_id' => $tree->id]);

    $this->postJson("/api/v1/trees/{$tree->id}/nodes/{$root->id}/attach", [
        'skill_id' => $existing->skill_id,
    ], $headers)->assertUnprocessable();
});

test('moves a node under a new parent identified by skill id', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);
    $nodeA = TreeNode::factory()->create(['tree_id' => $tree->id]);
    $nodeB = TreeNode::factory()->create(['tree_id' => $tree->id]);

    $this->postJson("/api/v1/trees/{$tree->id}/nodes/{$nodeB->id}/move", [
        'parent_skill_id' => $nodeA->skill_id,
    ], $headers)
        ->assertOk()
        ->assertJsonPath('data.parent_node_id', $nodeA->id);
});

test('rejects a move that would create a cycle', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);

    $nodeA = TreeNode::factory()->create(['tree_id' => $tree->id]);
    $nodeB = TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $nodeA->id]);
    $nodeC = TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $nodeB->id]);

    $this->postJson("/api/v1/trees/{$tree->id}/nodes/{$nodeA->id}/move", [
        'parent_skill_id' => $nodeC->skill_id,
    ], $headers)->assertUnprocessable();
});

test('detach_only reparents children to the removed node\'s parent', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);

    $nodeA = TreeNode::factory()->create(['tree_id' => $tree->id]);
    $nodeB = TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $nodeA->id]);
    $nodeC = TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $nodeB->id]);

    $this->deleteJson("/api/v1/trees/{$tree->id}/nodes/{$nodeB->id}", [
        'mode' => 'detach_only',
    ], $headers)->assertNoContent();

    $this->assertDatabaseMissing('tree_nodes', ['id' => $nodeB->id]);
    $this->assertDatabaseHas('tree_nodes', ['id' => $nodeC->id, 'parent_node_id' => $nodeA->id]);
});

test('delete_subtree removes the node and all of its descendants', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);

    $nodeA = TreeNode::factory()->create(['tree_id' => $tree->id]);
    $nodeB = TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $nodeA->id]);
    $nodeC = TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $nodeB->id]);

    $this->deleteJson("/api/v1/trees/{$tree->id}/nodes/{$nodeB->id}", [
        'mode' => 'delete_subtree',
    ], $headers)->assertNoContent();

    $this->assertDatabaseMissing('tree_nodes', ['id' => $nodeB->id]);
    $this->assertDatabaseMissing('tree_nodes', ['id' => $nodeC->id]);
    $this->assertDatabaseHas('tree_nodes', ['id' => $nodeA->id]);
});

test('rejects removing a node without specifying a mode', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);
    $node = TreeNode::factory()->create(['tree_id' => $tree->id]);

    $this->deleteJson("/api/v1/trees/{$tree->id}/nodes/{$node->id}", [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mode');
});

test('returns the full nested tree structure', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $tree = Tree::factory()->create(['workspace_id' => 111]);

    $root = TreeNode::factory()->create(['tree_id' => $tree->id]);
    $child = TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $root->id]);
    TreeNode::factory()->create(['tree_id' => $tree->id, 'parent_node_id' => $child->id]);

    $response = $this->getJson("/api/v1/trees/{$tree->id}/structure", $headers)->assertOk();

    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $root->id)
        ->assertJsonCount(1, 'data.0.children')
        ->assertJsonPath('data.0.children.0.id', $child->id)
        ->assertJsonCount(1, 'data.0.children.0.children');
});
