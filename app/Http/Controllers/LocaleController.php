<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['id', 'en'], true)) {
            $request->session()->put('locale', $locale);
            Cookie::queue('locale', $locale, 60 * 24 * 365);
        }

        return redirect()->back();
    }
}
