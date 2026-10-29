<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        // Custom roles inherit the capabilities of their base level.
        $allowed = $user && (
            in_array($user->role, $roles, true)
            || in_array($user->baseRole(), $roles, true)
        );

        if (! $allowed) {
            // Do not reveal that super-admin-only pages exist.
            abort(in_array('super_admin', $roles, true) ? 404 : 403, __('app.common.no_access'));
        }

        return $next($request);
    }
}
