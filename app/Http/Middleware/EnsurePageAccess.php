<?php

namespace App\Http\Middleware;

use App\Support\PageAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePageAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $permission = PageAccess::routePermission($request->route()?->getName());

        if ($user && $permission && ! PageAccess::allows($user, $permission)) {
            abort(403, __('app.common.no_access'));
        }

        return $next($request);
    }
}
