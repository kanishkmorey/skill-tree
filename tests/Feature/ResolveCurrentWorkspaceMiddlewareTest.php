<?php

use App\Support\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['36blocks.auth', 'workspace.resolve'])->get('/__test/workspace', function (Request $request) {
        $workspace = app(CurrentWorkspace::class);

        return response()->json([
            'workspace_id' => $workspace->workspaceId,
            'user_id' => $workspace->userId,
        ]);
    });
});

test('binds the current workspace from the authenticated user\'s current company', function () {
    $headers = actingAsWorkspace(workspaceId: 76851, userId: 166503);

    $this->getJson('/__test/workspace', $headers)
        ->assertOk()
        ->assertJson(['workspace_id' => 76851, 'user_id' => 166503]);
});

test('rejects requests when the authenticated user has no current company', function () {
    config(['services.36blocks.feature_configuration_id' => 171]);

    Http::fake([
        '*/c/getDetails' => Http::response([
            'data' => [['id' => 166503, 'name' => 'Test User', 'feature_configuration_id' => 171]],
            'status' => 'success',
            'hasError' => false,
            'errors' => [],
        ]),
    ]);

    $this->getJson('/__test/workspace', ['proxy_auth_token' => 'test-token'])
        ->assertForbidden();
});
