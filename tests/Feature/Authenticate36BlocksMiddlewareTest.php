<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('36blocks.auth')->get('/__test/36blocks', function (Request $request) {
        return response()->json(['user' => $request->attributes->get('user')]);
    });

    config(['services.36blocks.feature_configuration_id' => 171]);
});

function fakeGetDetailsResponse(int $featureConfigurationId = 171): void
{
    Http::fake([
        '*/c/getDetails' => Http::response([
            'data' => [
                ['id' => 166503, 'name' => 'Kanishk Morey', 'feature_configuration_id' => $featureConfigurationId],
            ],
            'status' => 'success',
            'hasError' => false,
            'errors' => [],
        ]),
    ]);
}

test('rejects requests without a proxy auth token', function () {
    $this->getJson('/__test/36blocks')->assertUnauthorized();
});

test('rejects requests when 36blocks reports a failure', function () {
    Http::fake(['*/c/getDetails' => Http::response(['errors' => ['invalid']], 401)]);

    $this->getJson('/__test/36blocks', ['proxy_auth_token' => 'bad-token'])
        ->assertUnauthorized();
});

test('rejects requests when the feature configuration id does not match', function () {
    fakeGetDetailsResponse(featureConfigurationId: 999);

    $this->getJson('/__test/36blocks', ['proxy_auth_token' => 'good-token'])
        ->assertForbidden();
});

test('passes the request through and attaches the user when the feature configuration id matches', function () {
    fakeGetDetailsResponse(featureConfigurationId: 171);

    $this->getJson('/__test/36blocks', ['proxy_auth_token' => 'good-token'])
        ->assertOk()
        ->assertJsonPath('user.id', 166503);
});
