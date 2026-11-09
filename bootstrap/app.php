<?php

use App\Http\Middleware\EnsurePageAccess;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTimezone;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Error pages for unmatched routes render before the web group runs,
        // so the locale cookie is kept readable for the global SetLocale.
        $middleware->encryptCookies(except: [
            'locale',
        ]);

        $middleware->prepend(SetLocale::class);
        $middleware->prepend(SetTimezone::class);

        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'page.access' => EnsurePageAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
