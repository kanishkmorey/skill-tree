<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class Authenticate36Blocks
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('proxy_auth_token');

        if (! $token) {
            abort(401, 'Missing proxy auth token.');
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'proxy_auth_token' => $token,
        ])->get(config('services.36blocks.base_url').'/c/getDetails');

        if ($response->failed()) {
            abort(401, 'Unable to verify proxy auth token.');
        }

        $user = $response->json('data.0');

        if (! $user) {
            abort(401, 'Invalid proxy auth token.');
        }

        if ((string) ($user['feature_configuration_id'] ?? null) !== (string) config('services.36blocks.feature_configuration_id')) {
            abort(403, 'Feature configuration does not match.');
        }

        $request->attributes->set('user', $user);

        return $next($request);
    }
}
