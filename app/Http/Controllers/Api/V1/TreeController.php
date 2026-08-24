<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachTreeNodeRequest;
use App\Http\Requests\DeleteTreeNodeRequest;
use App\Http\Requests\MoveTreeNodeRequest;
use App\Http\Requests\StoreTreeNodeRequest;
use App\Http\Requests\StoreTreeRequest;
use App\Http\Requests\UpdateTreeRequest;
use App\Http\Resources\TreeNodeResource;
use App\Http\Resources\TreeResource;
use App\Models\Tree;
use App\Models\TreeNode;
use App\Services\TreeNodeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TreeController extends Controller
{
    public function __construct(private readonly TreeNodeService $nodes) {}

    public function index(): AnonymousResourceCollection
    {
        return TreeResource::collection(Tree::latest()->get());
    }

    public function store(StoreTreeRequest $request): TreeResource
    {
        $tree = Tree::create($request->validated());

        return new TreeResource($tree);
    }

    public function show(Tree $tree): TreeResource
    {
        return new TreeResource($tree);
    }

    public function update(UpdateTreeRequest $request, Tree $tree): TreeResource
    {
        $tree->update($request->validated());

        return new TreeResource($tree);
    }

    public function destroy(Tree $tree): Response
    {
        $tree->delete();

        return response()->noContent();
    }

    public function structure(Tree $tree): AnonymousResourceCollection
    {
        return TreeNodeResource::collection($this->nodes->structure($tree));
    }

    public function storeNode(StoreTreeNodeRequest $request, Tree $tree): TreeNodeResource
    {
        $data = $request->validated();

        $node = $this->nodes->createBranch(
            tree: $tree,
            skillAttributes: [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
            parentNodeId: $data['parent_node_id'] ?? null,
        );

        return new TreeNodeResource($node->load('skill'));
    }

    public function attachNode(AttachTreeNodeRequest $request, Tree $tree, TreeNode $node): TreeNodeResource
    {
        abort_unless($node->tree_id === $tree->id, 404);

        $data = $request->validated();

        $attached = $this->nodes->attach(
            tree: $tree,
            targetNode: $node,
            skillId: $data['skill_id'] ?? null,
            nodeId: $data['node_id'] ?? null,
        );

        return new TreeNodeResource($attached->load('skill'));
    }

    public function moveNode(MoveTreeNodeRequest $request, Tree $tree, TreeNode $node): TreeNodeResource
    {
        abort_unless($node->tree_id === $tree->id, 404);

        $moved = $this->nodes->move($node, $request->validated('parent_skill_id'));

        return new TreeNodeResource($moved->load('skill'));
    }

    public function destroyNode(DeleteTreeNodeRequest $request, Tree $tree, TreeNode $node): Response
    {
        abort_unless($node->tree_id === $tree->id, 404);

        $this->nodes->detach($node, $request->validated('mode'));

        return response()->noContent();
    }
}
