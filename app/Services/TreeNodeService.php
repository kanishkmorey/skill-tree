<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\Tree;
use App\Models\TreeNode;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TreeNodeService
{
    /**
     * Create a new skill and place it as a node under the given parent (or as a root node).
     *
     * @param  array<string, mixed>  $skillAttributes
     */
    public function createBranch(Tree $tree, array $skillAttributes, ?int $parentNodeId): TreeNode
    {
        $this->assertNodeInTree($tree, $parentNodeId);

        $skill = Skill::create($skillAttributes);

        return TreeNode::create([
            'tree_id' => $tree->id,
            'skill_id' => $skill->id,
            'parent_node_id' => $parentNodeId,
        ]);
    }

    /**
     * Attach an existing skill, or re-attach an existing node (moving it from wherever it
     * currently lives), as a child of the given target node.
     */
    public function attach(Tree $tree, TreeNode $targetNode, ?int $skillId, ?int $nodeId): TreeNode
    {
        $this->assertNodeInTree($tree, $targetNode->id);

        if ($nodeId !== null) {
            $node = TreeNode::findOrFail($nodeId);

            $this->assertNoCycle($targetNode, $node);

            $node->tree_id = $tree->id;
            $node->parent_node_id = $targetNode->id;
            $node->save();

            return $node;
        }

        if (TreeNode::where('tree_id', $tree->id)->where('skill_id', $skillId)->exists()) {
            throw ValidationException::withMessages([
                'skill_id' => 'This skill already has a node in this tree.',
            ]);
        }

        return TreeNode::create([
            'tree_id' => $tree->id,
            'skill_id' => $skillId,
            'parent_node_id' => $targetNode->id,
        ]);
    }

    /**
     * Re-parent a node, identifying the new parent by the skill it holds in this tree.
     * A null skill id moves the node to the root of the tree.
     */
    public function move(TreeNode $node, ?int $parentSkillId): TreeNode
    {
        $parentNode = null;

        if ($parentSkillId !== null) {
            $parentNode = TreeNode::where('tree_id', $node->tree_id)
                ->where('skill_id', $parentSkillId)
                ->first();

            if (! $parentNode) {
                throw ValidationException::withMessages([
                    'parent_skill_id' => 'That skill does not have a node in this tree.',
                ]);
            }

            $this->assertNoCycle($parentNode, $node);
        }

        $node->parent_node_id = $parentNode?->id;
        $node->save();

        return $node;
    }

    /**
     * Remove a node from its tree. `detach_only` re-parents its children to its own parent
     * (or makes them roots); `delete_subtree` deletes the node and every descendant.
     */
    public function detach(TreeNode $node, string $mode): void
    {
        if ($mode === 'delete_subtree') {
            $this->deleteSubtree($node);

            return;
        }

        TreeNode::where('parent_node_id', $node->id)->update(['parent_node_id' => $node->parent_node_id]);
        $node->delete();
    }

    /**
     * Build the full nested structure of a tree in a single query, rooted at nodes with no parent.
     *
     * @return Collection<int, TreeNode>
     */
    public function structure(Tree $tree): Collection
    {
        $nodesByParent = $tree->nodes()->with('skill')->get()->groupBy('parent_node_id');

        $attachChildren = function (TreeNode $node) use (&$attachChildren, $nodesByParent): TreeNode {
            $children = $nodesByParent->get($node->id, collect())->map($attachChildren)->values();
            $node->setRelation('children', $children);

            return $node;
        };

        return $nodesByParent->get(null, collect())->map($attachChildren)->values();
    }

    private function deleteSubtree(TreeNode $node): void
    {
        TreeNode::where('parent_node_id', $node->id)->get()->each(
            fn (TreeNode $child) => $this->deleteSubtree($child)
        );

        $node->delete();
    }

    private function assertNodeInTree(Tree $tree, ?int $nodeId): void
    {
        if ($nodeId === null) {
            return;
        }

        if (! TreeNode::where('id', $nodeId)->where('tree_id', $tree->id)->exists()) {
            throw ValidationException::withMessages([
                'parent_node_id' => 'The parent node does not belong to this tree.',
            ]);
        }
    }

    /**
     * Reject the move/attach if $movingNode is $targetParent or one of its ancestors,
     * since re-parenting $movingNode under it would create a cycle.
     */
    private function assertNoCycle(TreeNode $targetParent, TreeNode $movingNode): void
    {
        $ancestor = $targetParent;

        while ($ancestor !== null) {
            if ($ancestor->id === $movingNode->id) {
                throw ValidationException::withMessages([
                    'node' => 'This would create a cycle in the tree.',
                ]);
            }

            $ancestor = $ancestor->parent_node_id ? TreeNode::find($ancestor->parent_node_id) : null;
        }
    }
}
