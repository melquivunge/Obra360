<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * Checks that the authenticated user's role is in the allowed $roles list.
     * Returns 403 if the user's role is not permitted.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check() || ! in_array(auth()->user()->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
