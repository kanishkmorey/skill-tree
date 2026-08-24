<?php

namespace App\Http\Middleware;

use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('user');

        $workspaceId = $user['currentCompany']['id'] ?? null;

        if (! $workspaceId) {
            abort(403, 'No active workspace for this user.');
        }

        app()->instance(CurrentWorkspace::class, new CurrentWorkspace(
            workspaceId: $workspaceId,
            userId: $user['id'],
        ));

        return $next($request);
    }
}
