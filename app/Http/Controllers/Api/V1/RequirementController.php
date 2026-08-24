<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequirementRequest;
use App\Http\Requests\UpdateRequirementRequest;
use App\Http\Resources\RequirementResource;
use App\Models\Requirement;
use App\Models\Skill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RequirementController extends Controller
{
    public function index(Skill $skill): AnonymousResourceCollection
    {
        return RequirementResource::collection($skill->requirements);
    }

    public function store(StoreRequirementRequest $request, Skill $skill): RequirementResource
    {
        $requirement = $skill->requirements()->create($request->validated());

        return new RequirementResource($requirement);
    }

    public function update(UpdateRequirementRequest $request, Requirement $requirement): RequirementResource
    {
        $requirement->update($request->validated());

        return new RequirementResource($requirement);
    }

    public function destroy(Requirement $requirement): Response
    {
        $requirement->delete();

        return response()->noContent();
    }
}
