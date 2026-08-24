<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachSkillResourceRequest;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Models\Skill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ResourceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ResourceResource::collection(Resource::latest()->get());
    }

    public function store(StoreResourceRequest $request): ResourceResource
    {
        $resource = Resource::create($request->validated());

        return new ResourceResource($resource);
    }

    public function show(Resource $resource): ResourceResource
    {
        return new ResourceResource($resource);
    }

    public function update(UpdateResourceRequest $request, Resource $resource): ResourceResource
    {
        $resource->update($request->validated());

        return new ResourceResource($resource);
    }

    public function destroy(Resource $resource): Response
    {
        $resource->delete();

        return response()->noContent();
    }

    public function attach(AttachSkillResourceRequest $request, Skill $skill): ResourceResource
    {
        $resourceId = $request->validated('resource_id');

        $skill->resources()->syncWithoutDetaching([$resourceId]);

        return new ResourceResource(Resource::findOrFail($resourceId));
    }

    public function detach(Skill $skill, Resource $resource): Response
    {
        $skill->resources()->detach($resource->id);

        return response()->noContent();
    }
}
