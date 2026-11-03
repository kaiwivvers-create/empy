<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // This middleware also runs globally, before the session starts, so
        // fall back to the locale cookie there (for example on a 404 page).
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;

        if (! in_array($locale, ['id', 'en'], true)) {
            $locale = $request->cookie('locale');
        }

        if (! in_array($locale, ['id', 'en'], true)) {
            $locale = config('app.locale', 'id');
        }

        App::setLocale(in_array($locale, ['id', 'en'], true) ? $locale : 'id');

        return $next($request);
    }
}
