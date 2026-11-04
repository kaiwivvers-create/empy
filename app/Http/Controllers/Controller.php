<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Return to a filtered index page after an action, dropping modal state.
     *
     * @param  list<string>  $allowed
     */
    protected function redirectToIndex(string $route, array $allowed = []): RedirectResponse
    {
        $fallback = route($route);
        $previous = url()->previous();

        if ($previous) {
            $previousPath = rtrim((string) parse_url($previous, PHP_URL_PATH), '/');
            $fallbackPath = rtrim((string) parse_url($fallback, PHP_URL_PATH), '/');

            if ($previousPath === $fallbackPath) {
                parse_str((string) parse_url($previous, PHP_URL_QUERY), $query);

                return redirect()->route($route, array_intersect_key($query, array_flip($allowed)));
            }
        }

        return redirect()->route($route);
    }
}
