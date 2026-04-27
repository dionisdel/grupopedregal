<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user's role slug matches any of the allowed roles.
     * Usage: ->middleware('role:admin,superadmin')
     *
     * @param  string  ...$roles  Allowed role slugs
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userRoleSlug = $user->role?->slug;

        if (!$userRoleSlug || !in_array($userRoleSlug, $roles, true)) {
            return response()->json([
                'message' => 'No tienes permisos para esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
