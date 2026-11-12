<?php

namespace App\Http\Controllers;

use App\Support\Captcha;
use App\Support\PageAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $email = (string) old('email', $request->session()->get('login_throttled_email', ''));

        return view('auth.login', [
            'captchas' => Captcha::challenges(),
            'turnstileEnabled' => Captcha::turnstileEnabled(),
            'email' => $email,
            'lockoutSeconds' => $email === '' ? 0 : $this->lockoutRemaining($request, $email),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = $this->normalizeEmail($credentials['email']);
        $remember = $request->boolean('remember');

        if (($seconds = $this->lockoutRemaining($request, $email)) > 0) {
            $request->session()->put('login_throttled_email', $email);

            throw ValidationException::withMessages([
                'email' => __('app.auth.throttled', ['seconds' => $seconds]),
            ]);
        }

        if ($captchaError = Captcha::verify($request)) {
            $this->registerFailure($request, $email);

            throw ValidationException::withMessages([
                'captcha' => __($captchaError),
            ]);
        }

        if (! Auth::attempt($credentials, $remember)) {
            if (($seconds = $this->registerFailure($request, $email)) > 0) {
                throw ValidationException::withMessages([
                    'email' => __('app.auth.throttled', ['seconds' => $seconds]),
                ]);
            }

            throw ValidationException::withMessages([
                'email' => __('app.auth.failed'),
            ]);
        }

        if (! $request->user()->is_active) {
            Auth::logout();
            $this->registerFailure($request, $email);

            throw ValidationException::withMessages([
                'email' => __('app.auth.inactive'),
            ]);
        }

        $this->clearFailures($request, $email);
        $request->session()->regenerate();

        return redirect()->intended(route(PageAccess::homeRoute($request->user())));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(Request $request, string $email): string
    {
        return 'login:'.$this->normalizeEmail($email).'|'.$request->ip();
    }

    private function lockoutKey(Request $request, string $email): string
    {
        return 'login-lockout:'.sha1($this->normalizeEmail($email).'|'.$request->ip());
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function lockoutRemaining(Request $request, string $email): int
    {
        $until = (int) Cache::get($this->lockoutKey($request, $email));

        return max(0, $until - now()->getTimestamp());
    }

    /**
     * Count a failed attempt and return the lockout length once the limit is hit.
     */
    private function registerFailure(Request $request, string $email): int
    {
        $attempts = RateLimiter::hit($this->throttleKey($request, $email), self::LOCKOUT_SECONDS);

        if ($attempts < self::MAX_ATTEMPTS) {
            return 0;
        }

        // Measure the lockout from the latest failure instead of the first one,
        // so the countdown matches how long the user is really blocked.
        Cache::put(
            $this->lockoutKey($request, $email),
            now()->addSeconds(self::LOCKOUT_SECONDS)->getTimestamp(),
            self::LOCKOUT_SECONDS,
        );
        $request->session()->put('login_throttled_email', $email);

        return self::LOCKOUT_SECONDS;
    }

    private function clearFailures(Request $request, string $email): void
    {
        RateLimiter::clear($this->throttleKey($request, $email));
        Cache::forget($this->lockoutKey($request, $email));
        $request->session()->forget('login_throttled_email');
    }
}
