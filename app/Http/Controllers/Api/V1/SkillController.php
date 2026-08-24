<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SkillController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SkillResource::collection(
            Skill::with(['requirements', 'resources', 'knowledge'])->latest()->get()
        );
    }

    public function store(StoreSkillRequest $request): SkillResource
    {
        // TODO: creating skill with duplicate name gives 500 server error rather than reason.
        $skill = Skill::create($request->validated());

        return new SkillResource($skill);
    }

    public function show(Skill $skill): SkillResource
    {
        return new SkillResource($skill->load(['requirements', 'resources', 'knowledge']));
    }

    public function update(UpdateSkillRequest $request, Skill $skill): SkillResource
    {
        $skill->update($request->validated());

        return new SkillResource($skill);
    }

    public function destroy(Skill $skill): Response
    {
        //TODO: there is no soft delete functionality, should there be??
        $skill->delete();

        return response()->noContent();
    }
}
