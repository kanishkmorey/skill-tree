<?php

use App\Http\Controllers\Api\V1\RequirementController;
use App\Http\Controllers\Api\V1\ResourceController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\SkillKnowledgeController;
use App\Http\Controllers\Api\V1\TreeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Version 1
Route::prefix('v1')->middleware(['36blocks.auth', 'workspace.resolve'])->group(function () {
    Route::apiResource('skills', SkillController::class);
    Route::apiResource('skills.requirements', RequirementController::class)->only(['index', 'store']);
    Route::patch('requirements/{requirement}', [RequirementController::class, 'update']);
    Route::delete('requirements/{requirement}', [RequirementController::class, 'destroy']);

    Route::get('skills/{skill}/knowledge', [SkillKnowledgeController::class, 'index']);
    Route::post('skills/{skill}/knowledge', [SkillKnowledgeController::class, 'store']);
    Route::patch('knowledge/{knowledge}', [SkillKnowledgeController::class, 'update']);
    Route::delete('knowledge/{knowledge}', [SkillKnowledgeController::class, 'destroy']);

    Route::apiResource('resources', ResourceController::class);
    Route::post('skills/{skill}/resources', [ResourceController::class, 'attach']);
    Route::delete('skills/{skill}/resources/{resource}', [ResourceController::class, 'detach']);

    Route::apiResource('trees', TreeController::class);
    Route::get('trees/{tree}/structure', [TreeController::class, 'structure']);
    Route::post('trees/{tree}/nodes', [TreeController::class, 'storeNode']);
    Route::post('trees/{tree}/nodes/{node}/attach', [TreeController::class, 'attachNode']);
    Route::post('trees/{tree}/nodes/{node}/move', [TreeController::class, 'moveNode']);
    Route::delete('trees/{tree}/nodes/{node}', [TreeController::class, 'destroyNode']);
});
