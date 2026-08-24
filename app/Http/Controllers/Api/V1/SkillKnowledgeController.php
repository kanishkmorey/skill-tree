<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSkillKnowledgeRequest;
use App\Http\Requests\UpdateSkillKnowledgeRequest;
use App\Http\Resources\SkillKnowledgeResource;
use App\Models\Skill;
use App\Models\SkillKnowledge;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SkillKnowledgeController extends Controller
{
    public function index(Skill $skill): AnonymousResourceCollection
    {
        return SkillKnowledgeResource::collection($skill->knowledge);
    }

    public function store(StoreSkillKnowledgeRequest $request, Skill $skill): SkillKnowledgeResource
    {
        $knowledge = $skill->knowledge()->create($request->validated());

        return new SkillKnowledgeResource($knowledge);
    }

    public function update(UpdateSkillKnowledgeRequest $request, SkillKnowledge $knowledge): SkillKnowledgeResource
    {
        $knowledge->update($request->validated());

        return new SkillKnowledgeResource($knowledge);
    }

    public function destroy(SkillKnowledge $knowledge): Response
    {
        $knowledge->delete();

        return response()->noContent();
    }
}
