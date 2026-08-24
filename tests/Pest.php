<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Fake a 36Blocks `/c/getDetails` response for the given workspace/user and return the
 * `proxy_auth_token` header needed to authenticate as them. Backed by a token => user
 * registry (rather than a single stubbed response) so a test can call this more than
 * once to act as several distinct workspaces/users without later calls clobbering
 * responses for tokens obtained from earlier calls.
 *
 * @return array<string, string>
 */
function actingAsWorkspace(int $workspaceId = 76851, int $userId = 166503): array
{
    static $registry = [];

    config(['services.36blocks.feature_configuration_id' => 171]);

    $token = "test-token-{$userId}-{$workspaceId}";

    $registry[$token] = [
        'id' => $userId,
        'name' => 'Test User',
        'feature_configuration_id' => 171,
        'currentCompany' => ['id' => $workspaceId],
    ];

    Http::fake(function ($request) use (&$registry) {
        $token = $request->header('proxy_auth_token');
        $token = is_array($token) ? ($token[0] ?? null) : $token;

        if (! isset($registry[$token])) {
            return Http::response(['errors' => ['Unknown token.']], 401);
        }

        return Http::response([
            'data' => [$registry[$token]],
            'status' => 'success',
            'hasError' => false,
            'errors' => [],
        ]);
    });

    return ['proxy_auth_token' => $token];
}
